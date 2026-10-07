<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\GameMatchPlayer;
use App\Services\GameService;
use App\Events\PoolMatchUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PoolController extends Controller
{
    protected $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    public function join(Request $request)
    {
        $request->validate([
            'bet_amount' => 'required|numeric|min:10',
            'game_type' => 'required|string|in:pool_1v1,pool_snooker',
        ]);

        $gameType = $request->input('game_type');
        $maxPlayers = ($gameType === 'pool_snooker') ? 5 : 2;

        try {
            $match = $this->gameService->joinMatch($request->user(), $gameType, $request->bet_amount, $maxPlayers);
            
            return response()->json([
                'match' => $match->load('players.user'),
                'room_code' => $match->room_code
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function getMatch($code)
    {
        $match = GameMatch::where('room_code', $code)->with('players.user')->firstOrFail();
        
        $state = $match->game_state;
        
        // Auto-check snooker countdown transition
        if ($match->status === 'waiting' && $match->game_type === 'pool_snooker' && isset($state['countdown_expires_at'])) {
            if (now()->timestamp >= $state['countdown_expires_at']) {
                $currentPlayerCount = $match->players()->count();
                if ($currentPlayerCount >= 3) {
                    $match->update(['status' => 'playing']);
                    $this->gameService->initializePoolSnookerState($match);
                    $match->refresh();
                    $state = $match->game_state;
                } else {
                    unset($state['countdown_expires_at']);
                    $match->update(['game_state' => $state]);
                }
            }
        }
        
        // Auto-check timeout on access
        if ($match->status === 'playing' && isset($state['last_action_at'])) {
            if (now()->timestamp - $state['last_action_at'] > 60) {
                $this->gameService->handleTimeout($match);
                $match->refresh();
            }
        }

        return response()->json($match);
    }

    public function submitStrike(Request $request, $code)
    {
        $match = GameMatch::where('room_code', $code)->firstOrFail();
        $user = $request->user();
        $state = $match->game_state;

        if ($state['status'] !== 'playing') {
            return response()->json(['message' => 'Match is not in playing state.'], 422);
        }

        $activePlayer = $state['players'][$state['turn_index']];

        if ($activePlayer['user_id'] != $user->id) {
            return response()->json(['message' => 'Not your turn.'], 422);
        }

        // Sync visual trajectory force, angles, and cue parameters
        $vx = $request->input('vx', 0);
        $vy = $request->input('vy', 0);
        $cueAngle = $request->input('cueAngle', 0);
        $force = $request->input('force', 0);

        // Record the strike parameters in last_shot
        $state['last_shot'] = [
            'user_id' => $user->id,
            'vx' => $vx,
            'vy' => $vy,
            'cueAngle' => $cueAngle,
            'force' => $force,
            'timestamp' => now()->timestamp
        ];

        $state['last_action_at'] = now()->timestamp;
        $match->update(['game_state' => $state]);

        broadcast(new PoolMatchUpdated($match->id, $state));

        return response()->json([
            'match' => $match,
            'message' => 'Strike successfully synced.'
        ]);
    }

    public function updateState(Request $request, $code)
    {
        $match = GameMatch::where('room_code', $code)->firstOrFail();
        $user = $request->user();
        $state = $match->game_state;

        if ($state['status'] !== 'playing') {
            return response()->json(['message' => 'Match is not active.'], 422);
        }

        // Only the active player should report the final physics outcome
        $activePlayer = $state['players'][$state['turn_index']];
        if ($activePlayer['user_id'] != $user->id) {
            return response()->json(['message' => 'Only the active player can report physics outcome.'], 422);
        }

        $balls = $request->input('balls');
        $scores = $request->input('scores'); // array of scores matching player order
        $groups = $request->input('groups'); // array of groups (solids/stripes) matching player order
        $turnIndex = $request->input('next_turn_index');
        $winnerId = $request->input('winner_id');

        if ($balls) {
            $state['balls'] = $balls;
        }

        if ($scores) {
            foreach ($scores as $index => $score) {
                if (isset($state['players'][$index])) {
                    $state['players'][$index]['score'] = $score;
                }
            }
        }

        if ($groups) {
            foreach ($groups as $index => $group) {
                if (isset($state['players'][$index])) {
                    $state['players'][$index]['group'] = $group;
                }
            }
        }

        // Check if a player has won
        if ($winnerId) {
            $winner = \App\Models\User::find($winnerId);
            if ($winner) {
                $result = $this->gameService->finalizeMatch($match, $winner);
                $state['status'] = 'finished';
                $state['winner_id'] = $winnerId;
                $state['payout'] = $result['payout'];
                $state['message'] = "Game Over! Winner: " . $winner->username;
                
                $match->update(['game_state' => $state]);
                broadcast(new PoolMatchUpdated($match->id, $state));

                return response()->json([
                    'match' => $match,
                    'payout' => $result['payout'],
                    'message' => $state['message']
                ]);
            }
        }

        // Update turn index
        if (isset($turnIndex)) {
            $state['turn_index'] = $turnIndex;
        }

        $state['last_action_at'] = now()->timestamp;
        $state['last_shot'] = null; // Reset shot state
        $match->update(['game_state' => $state]);

        broadcast(new PoolMatchUpdated($match->id, $state));

        return response()->json([
            'match' => $match,
            'message' => 'State updated successfully.'
        ]);
    }

    public function activeMatch(Request $request)
    {
        $existing = GameMatchPlayer::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->whereHas('match', function ($q) {
                $q->whereIn('game_type', ['pool_1v1', 'pool_snooker'])
                  ->whereIn('status', ['waiting', 'playing']);
            })->first();

        if ($existing) {
            $match = $existing->match;
            $state = $match->game_state;
            
            // Auto-check snooker countdown transition
            if ($match->status === 'waiting' && $match->game_type === 'pool_snooker' && isset($state['countdown_expires_at'])) {
                if (now()->timestamp >= $state['countdown_expires_at']) {
                    $currentPlayerCount = $match->players()->count();
                    if ($currentPlayerCount >= 3) {
                        $match->update(['status' => 'playing']);
                        $this->gameService->initializePoolSnookerState($match);
                        $match->refresh();
                        $state = $match->game_state;
                    } else {
                        unset($state['countdown_expires_at']);
                        $match->update(['game_state' => $state]);
                    }
                }
            }

            // Auto-check timeout
            if ($match->status === 'playing' && isset($state['last_action_at'])) {
                if (now()->timestamp - $state['last_action_at'] > 60) {
                    $this->gameService->handleTimeout($match);
                    $match->refresh();
                }
            }

            return response()->json([
                'match' => $match->load('players.user'),
                'room_code' => $match->room_code
            ]);
        }

        return response()->json(['match' => null]);
    }

    public function cancelMatch(Request $request)
    {
        $user = $request->user();

        return DB::transaction(function () use ($user) {
            $existing = GameMatchPlayer::where('user_id', $user->id)
                ->where('status', 'active')
                ->whereHas('match', function ($q) {
                    $q->whereIn('game_type', ['pool_1v1', 'pool_snooker'])
                      ->where('status', 'waiting');
                })->first();

            if (!$existing) {
                return response()->json(['message' => 'No waiting match found.'], 422);
            }

            $match = $existing->match;
            $betAmount = $existing->bet_amount;

            // Refund balance
            $user->increment('balance', $betAmount);

            // Delete player
            $existing->delete();

            // Delete match if empty, otherwise decrement pot
            $remainingPlayers = $match->players()->count();
            if ($remainingPlayers === 0) {
                $match->delete();
            } else {
                $match->decrement('total_pot', $betAmount);
                
                // If it is snooker, clear countdown if count drops below 3
                if ($match->game_type === 'pool_snooker') {
                    $state = $match->game_state ?? [];
                    if ($remainingPlayers < 3) {
                        if (isset($state['countdown_expires_at'])) {
                            unset($state['countdown_expires_at']);
                        }
                    }
                    $state['players_count'] = $remainingPlayers;
                    $match->update(['game_state' => $state]);
                    broadcast(new PoolMatchUpdated($match->id, $state));
                }
            }

            return response()->json([
                'message' => 'Matchmaking cancelled successfully.',
                'balance' => $user->balance
            ]);
        });
    }

    public function forfeit(Request $request, $code)
    {
        $match = GameMatch::where('room_code', $code)->firstOrFail();
        $user = $request->user();

        if ($match->status !== 'playing') {
            return response()->json(['message' => 'Match not active.'], 422);
        }

        $player = $match->players()->where('user_id', $user->id)->first();
        if (!$player) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->gameService->handleForfeit($match, $user);

        return response()->json(['message' => 'Forfeited']);
    }
}
