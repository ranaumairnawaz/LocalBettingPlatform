<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameMatch;
use App\Models\GameMatchPlayer;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\GameService;
use App\Services\ThreePattiEvaluator;
use App\Events\ThreePattiTableUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThreePattiController extends Controller
{
    protected $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    /**
     * Get settings and active dynamic boot tiers
     */
    public function getBoots()
    {
        $bootsSetting = SystemSetting::get('three_patti_boots');
        if (!$bootsSetting) {
            // Seed default values dynamically if missing
            $bootsSetting = json_encode([10, 50, 100, 500, 1000]);
            SystemSetting::create([
                'key' => 'three_patti_boots',
                'value' => $bootsSetting,
                'type' => 'array'
            ]);
        }
        return response()->json(json_decode($bootsSetting));
    }

    /**
     * Join an active table or spawn a new one with a dynamic boot amount
     */
    public function joinTable(Request $request)
    {
        $request->validate([
            'boot_amount' => 'required|numeric|min:10',
        ]);

        $user = $request->user();
        $bootAmount = (float) $request->boot_amount;

        if ($user->balance < $bootAmount) {
            return response()->json(['message' => 'Insufficient balance for this table.'], 422);
        }

        return DB::transaction(function () use ($user, $bootAmount) {
            // Check if already in an active match
            $existing = GameMatchPlayer::where('user_id', $user->id)
                ->where('status', 'active')
                ->whereHas('match', function ($q) {
                    $q->where('game_type', 'teen_patti')
                        ->whereIn('status', ['waiting', 'playing']);
                })->first();

            if ($existing) {
                return response()->json([
                    'room' => $this->secureGameState($existing->match, $user->id),
                    'room_code' => $existing->match->room_code
                ]);
            }

            // Find an open table waiting for players with matching boot
            $match = GameMatch::where('game_type', 'teen_patti')
                ->whereIn('status', ['waiting', 'playing', 'finished'])
                ->where('total_pot', '<', $bootAmount * 6) // Ensure it is not full
                ->whereHas('players', function ($q) use ($bootAmount) {
                    $q->where('bet_amount', $bootAmount);
                })
                ->withCount('players')
                ->having('players_count', '<', 6)
                ->oldest()
                ->lockForUpdate()
                ->first();

            if (!$match) {
                // Spawn a new luxury neon cards table
                $match = GameMatch::create([
                    'room_code' => strtoupper(Str::random(8)),
                    'game_type' => 'teen_patti',
                    'status' => 'waiting',
                    'total_pot' => 0,
                    'game_state' => [
                        'pot' => 0.00,
                        'boot' => $bootAmount,
                        'current_bet_unit' => $bootAmount,
                        'turn_user_id' => null,
                        'dealer_index' => 0,
                        'last_action_at' => now()->timestamp,
                        'sideshow_request' => null
                    ]
                ]);
            }

            // Deduct boot ante
            $user->decrement('balance', $bootAmount);

            // Assign seat index (0-5)
            $seatIndex = $match->players()->count();

            // Add player
            $match->players()->create([
                'user_id' => $user->id,
                'bet_amount' => $bootAmount, // Boot contribution held for next round or current
                'status' => 'active',
                'color' => (string) $seatIndex, // Seat index mapped to color column
            ]);

            $state = $match->game_state;
            if (!isset($state['players'])) {
                $state['players'] = [];
            }

            if (in_array($match->status, ['playing', 'finished'])) {
                // Queue for next round
                $state['players'][$user->id] = [
                    'cards' => [],
                    'is_seen' => false,
                    'is_folded' => true,
                    'is_waiting_next_round' => true,
                    'total_contributed' => 0.00
                ];
                $match->update(['game_state' => $state]);
                broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
            } else {
                // Active waiting lobby join
                $state['pot'] += $bootAmount;
                $match->increment('total_pot', $bootAmount);

                $playerCount = $match->players()->count();

                // Auto-start table if it fills up to 6 players, or start countdown if >= 3
                if ($playerCount >= 6) {
                    $match->status = 'playing';
                    $this->initializeGame($match);
                } else {
                    if ($playerCount >= 3 && !isset($state['countdown_expires_at'])) {
                        $state['countdown_expires_at'] = now()->timestamp + 12; // 12 seconds to takeoff
                    }
                    $match->update(['game_state' => $state]);
                    broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
                }
            }

            return response()->json([
                'room' => $this->secureGameState($match, $user->id),
                'room_code' => $match->room_code
            ]);
        });
    }

    /**
     * Get secure table state
     */
    public function getTable(Request $request, $code)
    {
        $match = GameMatch::where('room_code', $code)->with('players.user')->firstOrFail();

        // Auto-start if countdown expired in lobby waiting phase
        if ($match->status === 'waiting' && isset($match->game_state['countdown_expires_at'])) {
            if (now()->timestamp >= $match->game_state['countdown_expires_at'] && $match->players()->count() >= 3) {
                $match->update(['status' => 'playing']);
                $this->initializeGame($match);
                $match->refresh();
            }
        }

        // Auto-start next round if finished
        if ($match->status === 'finished' && isset($match->game_state['next_round_at'])) {
            if (now()->timestamp >= $match->game_state['next_round_at']) {
                $this->startNextRound($match);
                $match->refresh();
            }
        }

        return response()->json([
            'room' => $this->secureGameState($match, $request->user()->id)
        ]);
    }

    /**
     * Look at dealt cards privately (seen action)
     */
    public function lookAtCards(Request $request)
    {
        $user = $request->user();
        $match = $this->getActivePlayingMatch($user);

        if (!$match)
            return response()->json(['message' => 'No active playing room.'], 404);

        $state = $match->game_state;
        if (isset($state['players'][$user->id])) {
            $state['players'][$user->id]['is_seen'] = true;
            $match->update(['game_state' => $state]);
            broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
        }

        return response()->json([
            'cards' => $state['players'][$user->id]['cards'],
            'room' => $this->secureGameState($match, $user->id)
        ]);
    }

    /**
     * Place a Chaal (standard turn bet match)
     */
    public function placeChaal(Request $request)
    {
        $user = $request->user();
        $match = $this->getActivePlayingMatch($user);

        if (!$match)
            return response()->json(['message' => 'No active playing room.'], 404);

        $state = $match->game_state;

        if ((int) $state['turn_user_id'] !== $user->id) {
            return response()->json(['message' => 'It is not your turn.'], 403);
        }

        $pState = $state['players'][$user->id];
        $isSeen = $pState['is_seen'];

        // If playing Seen, must bet 2x bet unit. If Blind, plays 1x.
        $betAmount = $isSeen ? ($state['current_bet_unit'] * 2) : $state['current_bet_unit'];

        if ($user->balance < $betAmount) {
            return response()->json(['message' => 'Insufficient balance to place Chaal.'], 422);
        }

        DB::transaction(function () use ($user, $match, $betAmount) {
            $user->decrement('balance', $betAmount);

            $state = $match->game_state;
            $state['players'][$user->id]['total_contributed'] += $betAmount;
            $state['pot'] += $betAmount;
            $match->increment('total_pot', $betAmount);

            // Rotate turn to next active player
            $this->rotateTurn($match, $state);
        });

        return response()->json(['success' => true]);
    }

    /**
     * Raise the bet unit value
     */
    public function placeRaise(Request $request)
    {
        $request->validate([
            'new_bet_unit' => 'required|numeric'
        ]);

        $user = $request->user();
        $match = $this->getActivePlayingMatch($user);

        if (!$match)
            return response()->json(['message' => 'No active room.'], 404);

        $state = $match->game_state;
        if ((int) $state['turn_user_id'] !== $user->id) {
            return response()->json(['message' => 'It is not your turn.'], 403);
        }

        $newUnit = (float) $request->new_bet_unit;
        $currentUnit = (float) $state['current_bet_unit'];

        // Capped Raise options to avoid instant table wipes (must be greater than current and at most 2x current)
        if ($newUnit <= $currentUnit || $newUnit > $currentUnit * 2) {
            return response()->json(['message' => 'Invalid raise amount. Limit: 2x current unit.'], 422);
        }

        $pState = $state['players'][$user->id];
        $isSeen = $pState['is_seen'];
        $betAmount = $isSeen ? ($newUnit * 2) : $newUnit;

        if ($user->balance < $betAmount) {
            return response()->json(['message' => 'Insufficient balance for this raise.'], 422);
        }

        DB::transaction(function () use ($user, $match, $betAmount, $newUnit) {
            $user->decrement('balance', $betAmount);

            $state = $match->game_state;
            $state['current_bet_unit'] = $newUnit;
            $state['players'][$user->id]['total_contributed'] += $betAmount;
            $state['pot'] += $betAmount;
            $match->increment('total_pot', $betAmount);

            // Rotate turn
            $this->rotateTurn($match, $state);
        });

        return response()->json(['success' => true]);
    }

    /**
     * Fold hand (Pack)
     */
    public function packHand(Request $request)
    {
        $user = $request->user();
        $match = $this->getActivePlayingMatch($user);

        if (!$match)
            return response()->json(['message' => 'No active room.'], 404);

        $state = $match->game_state;
        if (!isset($state['players'][$user->id]) || $state['players'][$user->id]['is_folded']) {
            return response()->json(['message' => 'Already folded.'], 422);
        }

        $state['players'][$user->id]['is_folded'] = true;

        $match->update(['game_state' => $state]);

        // Verify remaining players
        $activePlayers = array_filter($state['players'], fn($p) => !$p['is_folded']);

        if (count($activePlayers) === 1) {
            // Last remaining player wins pot immediately
            $winnerId = array_key_first($activePlayers);
            $this->endGame($match, User::find($winnerId));
        } else {
            // If it was their turn, rotate it
            if ((int) $state['turn_user_id'] === $user->id) {
                $this->rotateTurn($match, $state);
            } else {
                broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Propose a private card comparison (Sideshow)
     */
    public function requestSideshow(Request $request)
    {
        $user = $request->user();
        $match = $this->getActivePlayingMatch($user);

        if (!$match)
            return response()->json(['message' => 'No active room.'], 404);

        $state = $match->game_state;
        if ((int) $state['turn_user_id'] !== $user->id) {
            return response()->json(['message' => 'Not your turn.'], 403);
        }

        // Identify previous active player in clockwise seat order
        $players = $match->players()->orderBy('color', 'asc')->get();
        $currentSeat = (int) $match->players()->where('user_id', $user->id)->first()->color;

        $prevPlayerId = null;
        for ($i = 1; $i <= count($players); $i++) {
            $seatToCheck = ($currentSeat - $i + count($players)) % count($players);
            $playerAtSeat = $players->where('color', (string) $seatToCheck)->first();
            if ($playerAtSeat && isset($state['players'][$playerAtSeat->user_id])) {
                $pState = $state['players'][$playerAtSeat->user_id];
                if (!$pState['is_folded']) {
                    $prevPlayerId = $playerAtSeat->user_id;
                    break;
                }
            }
        }

        if (!$prevPlayerId) {
            return response()->json(['message' => 'No active previous player found.'], 422);
        }

        // Rule: Both sideshow players MUST be Seen players
        if (!$state['players'][$user->id]['is_seen'] || !$state['players'][$prevPlayerId]['is_seen']) {
            return response()->json(['message' => 'Both players must be SEEN to request sideshow.'], 422);
        }

        // Place bet amount to request Sideshow (equal to standard Chaal cost)
        $betAmount = $state['current_bet_unit'] * 2;
        if ($user->balance < $betAmount) {
            return response()->json(['message' => 'Insufficient balance to request sideshow.'], 422);
        }

        DB::transaction(function () use ($user, $match, $betAmount, $prevPlayerId) {
            $user->decrement('balance', $betAmount);

            $state = $match->game_state;
            $state['players'][$user->id]['total_contributed'] += $betAmount;
            $state['pot'] += $betAmount;
            $match->increment('total_pot', $betAmount);

            // Open sideshow dialog request state
            $state['sideshow_request'] = [
                'requester_id' => $user->id,
                'target_id' => $prevPlayerId,
            ];

            $match->update(['game_state' => $state]);
            broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
        });

        return response()->json(['success' => true]);
    }

    /**
     * Accept or decline a sideshow compromise request
     */
    public function respondSideshow(Request $request)
    {
        $request->validate(['accept' => 'required|boolean']);
        $user = $request->user();
        $match = $this->getActivePlayingMatch($user);

        if (!$match)
            return response()->json(['message' => 'No active match.'], 404);

        $state = $match->game_state;
        $req = $state['sideshow_request'] ?? null;

        if (!$req || (int) $req['target_id'] !== $user->id) {
            return response()->json(['message' => 'No active sideshow request pending for you.'], 403);
        }

        $requesterId = $req['requester_id'];

        if ($request->accept) {
            // Compare hands privately using Evaluator
            $evalRequester = ThreePattiEvaluator::evaluateHand($state['players'][$requesterId]['cards']);
            $evalTarget = ThreePattiEvaluator::evaluateHand($state['players'][$user->id]['cards']);

            $loserId = null;
            if ($evalRequester['score'] >= $evalTarget['score']) {
                $loserId = $user->id; // target loses, requester has stronger hand
            } else {
                $loserId = $requesterId; // requester loses
            }

            // Mark loser as folded
            $state['players'][$loserId]['is_folded'] = true;
            $state['sideshow_request'] = null;

            $match->update(['game_state' => $state]);

            $activePlayers = array_filter($state['players'], fn($p) => !$p['is_folded']);
            if (count($activePlayers) === 1) {
                $winnerId = array_key_first($activePlayers);
                $this->endGame($match, User::find($winnerId));
            } else {
                $this->rotateTurn($match, $state);
            }
        } else {
            // Declined sideshow request
            $state['sideshow_request'] = null;
            $this->rotateTurn($match, $state);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Trigger a card Showdown (Show)
     */
    public function triggerShow(Request $request)
    {
        $user = $request->user();
        $match = $this->getActivePlayingMatch($user);

        if (!$match)
            return response()->json(['message' => 'No active match.'], 404);

        $state = $match->game_state;
        if ((int) $state['turn_user_id'] !== $user->id) {
            return response()->json(['message' => 'Not your turn.'], 403);
        }

        $activePlayers = array_filter($state['players'], fn($p) => !$p['is_folded']);
        if (count($activePlayers) !== 2) {
            return response()->json(['message' => 'Show can only be requested when exactly 2 players remain.'], 422);
        }

        // Determine Show bet cost
        $isSeen = $state['players'][$user->id]['is_seen'];
        $betAmount = $isSeen ? ($state['current_bet_unit'] * 2) : $state['current_bet_unit'];

        if ($user->balance < $betAmount) {
            return response()->json(['message' => 'Insufficient balance for Show.'], 422);
        }

        DB::transaction(function () use ($user, $match, $betAmount, $activePlayers) {
            $user->decrement('balance', $betAmount);

            $state = $match->game_state;
            $state['players'][$user->id]['total_contributed'] += $betAmount;
            $state['pot'] += $betAmount;
            $match->increment('total_pot', $betAmount);

            // Evaluate both hands
            $keys = array_keys($activePlayers);
            $p1 = $keys[0];
            $p2 = $keys[1];

            $eval1 = ThreePattiEvaluator::evaluateHand($state['players'][$p1]['cards']);
            $eval2 = ThreePattiEvaluator::evaluateHand($state['players'][$p2]['cards']);

            $winnerId = null;
            if ($eval1['score'] >= $eval2['score']) {
                $winnerId = $p1;
            } else {
                $winnerId = $p2;
            }

            $this->endGame($match, User::find($winnerId));
        });

        return response()->json(['success' => true]);
    }

    /**
     * Cancel match/refund in lobby waiting phase
     */
    public function leaveTable(Request $request)
    {
        $user = $request->user();

        $existing = GameMatchPlayer::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('match', function ($q) {
                $q->where('game_type', 'teen_patti')
                    ->whereIn('status', ['waiting', 'playing']);
            })->first();

        if (!$existing)
            return response()->json(['message' => 'Not on any table.']);

        $match = $existing->match;
        if ($match->status === 'waiting') {
            DB::transaction(function () use ($user, $existing, $match) {
                $user->increment('balance', $existing->bet_amount);
                $existing->delete();

                $state = $match->game_state;
                $state['pot'] = max(0, $state['pot'] - $existing->bet_amount);
                $match->decrement('total_pot', $existing->bet_amount);

                if ($match->players()->count() === 0) {
                    $match->update(['status' => 'cancelled']);
                } else {
                    // Recalculate countdown
                    if ($match->players()->count() < 3 && isset($state['countdown_expires_at'])) {
                        unset($state['countdown_expires_at']);
                    }
                    $match->update(['game_state' => $state]);
                    broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
                }
            });
            return response()->json(['message' => 'Successfully left the table.']);
        } else {
            // Forfeit match if left during active play
            DB::transaction(function () use ($user, $match) {
                $state = $match->game_state;
                $state['players'][$user->id]['is_folded'] = true;
                $match->update(['game_state' => $state]);

                $activePlayers = array_filter($state['players'], fn($p) => !$p['is_folded']);
                if (count($activePlayers) === 1) {
                    $winnerId = array_key_first($activePlayers);
                    $this->endGame($match, User::find($winnerId));
                } else {
                    if ((int) $state['turn_user_id'] === $user->id) {
                        $this->rotateTurn($match, $state);
                    } else {
                        broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
                    }
                }
            });
            return response()->json(['message' => 'You folded by leaving the live table.']);
        }
    }

    /**
     * Secure game state: Strips other player cards from the response to prevent client-side hacks!
     */
    private function secureGameState(GameMatch $match, $activeUserId = null)
    {
        $state = $match->game_state;
        if (!isset($state['players']))
            return $state;

        // Load visual seating info
        $players = $match->players()->with('user:id,username')->get();
        $state['status'] = $match->status;
        $state['room_code'] = $match->room_code;
        $state['total_pot'] = $match->total_pot;
        $state['next_round_at'] = $match->game_state['next_round_at'] ?? null;

        // Build elegant seats state
        $state['seats'] = $players->map(function ($p) use ($state, $activeUserId) {
            $cards = [];
            $isWinner = $match->winner_id === $p->user_id;

            // Show cards only if game is finished, player folded, or it belongs to the active user
            if ($match->status === 'finished' || $isWinner) {
                $cards = $state['players'][$p->user_id]['cards'] ?? [];
            } elseif ($activeUserId && (int) $p->user_id === (int) $activeUserId) {
                $cards = $state['players'][$p->user_id]['cards'] ?? [];
            }

            return [
                'user_id' => $p->user_id,
                'username' => $p->user->username,
                'seat_index' => (int) $p->color,
                'bet_contributed' => $state['players'][$p->user_id]['total_contributed'] ?? 0.00,
                'is_seen' => $state['players'][$p->user_id]['is_seen'] ?? false,
                'is_folded' => $state['players'][$p->user_id]['is_folded'] ?? false,
                'is_waiting_next_round' => $state['players'][$p->user_id]['is_waiting_next_round'] ?? false,
                'cards' => $cards,
                'card_rank_name' => ($match->status === 'finished' && count($cards) === 3)
                    ? ThreePattiEvaluator::evaluateHand($cards)['name']
                    : null
            ];
        });

        // Hide master state cards array completely from API responses
        unset($state['players']);

        return $state;
    }

    /**
     * Fetch active playing room
     */
    private function getActivePlayingMatch(User $user)
    {
        $existing = GameMatchPlayer::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('match', function ($q) {
                $q->where('game_type', 'teen_patti')
                    ->where('status', 'playing');
            })->first();

        return $existing ? $existing->match : null;
    }

    /**
     * Deal cards and start playing
     */
    private function initializeGame(GameMatch $match)
    {
        $players = $match->players()->get();

        // Generate deck
        $suits = ['H', 'D', 'S', 'C'];
        $ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        $deck = [];
        foreach ($suits as $s) {
            foreach ($ranks as $r) {
                $deck[] = $r . $s;
            }
        }
        shuffle($deck);

        // Deal 3 cards to each player
        $playersState = [];
        foreach ($players as $p) {
            $cards = [array_shift($deck), array_shift($deck), array_shift($deck)];
            $playersState[$p->user_id] = [
                'cards' => $cards,
                'is_seen' => false,
                'is_folded' => false,
                'total_contributed' => (float) $match->game_state['boot']
            ];
        }

        $state = $match->game_state;
        $state['players'] = $playersState;
        $state['turn_user_id'] = $players[0]->user_id; // First joining player starts
        $state['last_action_at'] = now()->timestamp;

        $match->update([
            'game_state' => $state,
            'status' => 'playing'
        ]);

        broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
    }

    /**
     * Turn rotation
     */
    private function rotateTurn(GameMatch $match, array $state)
    {
        $players = $match->players()->orderBy('color', 'asc')->get();
        $currentPlayerId = (int) $state['turn_user_id'];
        $currentSeat = (int) $match->players()->where('user_id', $currentPlayerId)->first()->color;

        // Find next seat clockwise that is active and not folded
        $nextPlayerId = null;
        for ($i = 1; $i <= count($players); $i++) {
            $seatToCheck = ($currentSeat + $i) % count($players);
            $playerAtSeat = $players->where('color', (string) $seatToCheck)->first();
            if ($playerAtSeat && isset($state['players'][$playerAtSeat->user_id])) {
                $pState = $state['players'][$playerAtSeat->user_id];
                if (!$pState['is_folded']) {
                    $nextPlayerId = $playerAtSeat->user_id;
                    break;
                }
            }
        }

        if ($nextPlayerId) {
            $state['turn_user_id'] = $nextPlayerId;
            $state['last_action_at'] = now()->timestamp;
            $match->update(['game_state' => $state]);
            broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
        } else {
            // Fallback in case of single player remaining
            $activePlayers = array_filter($state['players'], fn($p) => !$p['is_folded']);
            if (count($activePlayers) === 1) {
                $this->endGame($match, User::find(array_key_first($activePlayers)));
            }
        }
    }

    /**
     * Terminate game and award Pot minus commission
     */
    private function endGame(GameMatch $match, User $winner)
    {
        $game = Game::where('slug', 'teen_patti')->first();
        $commissionRate = $game ? $game->commission_rate : 10; // Default 10%

        $totalPot = (float) $match->total_pot;
        $commission = $totalPot * ($commissionRate / 100);
        $netPayout = $totalPot - $commission;

        DB::transaction(function () use ($match, $winner, $netPayout, $commission, $game) {
            // Credit winner balance
            $winner->increment('balance', $netPayout);

            // Log game transaction log
            $match->winner_id = $winner->id;
            $match->status = 'finished';
            $match->save();

            // Settle individual player match logs
            $players = $match->players()->get();
            foreach ($players as $p) {
                $pState = $match->game_state['players'][$p->user_id] ?? [];
                $isQueued = $pState['is_waiting_next_round'] ?? false;

                if (!$isQueued) {
                    $isWinner = $p->user_id === $winner->id;
                    $p->update(['status' => $isWinner ? 'won' : 'lost']);

                    if ($game) {
                        \App\Models\GameLog::create([
                            'user_id' => $p->user_id,
                            'game_id' => $game->id,
                            'bet_amount' => $pState['total_contributed'] ?? 0,
                            'payout_amount' => $isWinner ? $netPayout : 0,
                            'commission_amount' => $isWinner ? $commission : 0,
                            'result' => $isWinner ? 'win' : 'loss',
                            'game_data' => $pState,
                            'status' => 'completed'
                        ]);
                    }
                }
            }
        });

        // Broadcast showdown game state
        $state = $match->game_state;
        $state['winner'] = $winner->username;
        $state['payout'] = $netPayout;
        $state['commission'] = $commission;
        $state['status'] = 'finished';
        $state['next_round_at'] = now()->timestamp + 8; // Multi-round queueing delay

        $match->update(['game_state' => $state]);
        broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
    }

    /**
     * Start the next round, booting empty balance players
     */
    public function startNextRound(GameMatch $match)
    {
        return DB::transaction(function () use ($match) {
            $state = $match->game_state;
            $bootAmount = (float) $state['boot'];

            $players = $match->players()->get();
            $activeCount = 0;

            $playersState = [];
            $newPot = 0;

            foreach ($players as $p) {
                $user = $p->user;
                $pState = $state['players'][$p->user_id] ?? [];

                $isQueued = $pState['is_waiting_next_round'] ?? false;

                if (!$isQueued) {
                    if ($user->balance < $bootAmount) {
                        $p->delete();
                        continue;
                    }
                    $user->decrement('balance', $bootAmount);
                    $p->update(['bet_amount' => $bootAmount]);
                }

                $p->update(['status' => 'active']);
                $newPot += $bootAmount;
                $activeCount++;

                $playersState[$p->user_id] = [
                    'cards' => [],
                    'is_seen' => false,
                    'is_folded' => false,
                    'total_contributed' => $bootAmount
                ];
            }

            $match->update(['total_pot' => $newPot, 'winner_id' => null]);

            if ($activeCount < 3) {
                $match->update(['status' => 'waiting']);
                $state['pot'] = $newPot;
                $state['players'] = $playersState;
                unset($state['next_round_at']);
                unset($state['countdown_expires_at']);
                $match->update(['game_state' => $state]);
                broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
                return;
            }

            $suits = ['H', 'D', 'S', 'C'];
            $ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
            $deck = [];
            foreach ($suits as $s) {
                foreach ($ranks as $r) {
                    $deck[] = $r . $s;
                }
            }
            shuffle($deck);

            foreach ($playersState as $uid => &$pState) {
                $pState['cards'] = [array_shift($deck), array_shift($deck), array_shift($deck)];
            }

            $state['players'] = $playersState;
            $state['pot'] = $newPot;
            $state['current_bet_unit'] = $bootAmount;
            unset($state['winner']);
            unset($state['payout']);
            unset($state['commission']);
            unset($state['next_round_at']);

            $playerIds = array_keys($playersState);
            $state['turn_user_id'] = $playerIds[array_rand($playerIds)];
            $state['last_action_at'] = now()->timestamp;
            $state['sideshow_request'] = null;

            $match->update(['game_state' => $state, 'status' => 'playing']);
            broadcast(new ThreePattiTableUpdated($match->id, $this->secureGameState($match)));
        });
    }
}
