<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\GameMatchPlayer;
use App\Services\GameService;
use App\Events\Cricket3DMatchUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Cricket3DController extends Controller
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
        ]);

        try {
            $match = $this->gameService->joinMatch($request->user(), 'cricket3d', $request->bet_amount, 2);
            
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
        
        // Auto-check timeout on access
        $state = $match->game_state;
        if ($match->status === 'playing' && isset($state['last_action_at'])) {
            if (now()->timestamp - $state['last_action_at'] > 40) {
                $this->gameService->handleTimeout($match);
                $match->refresh();
            }
        }

        return response()->json($match);
    }

    private function startMatch(GameMatch $match)
    {
        $players = $match->players()->pluck('user_id')->toArray();
        
        $gameState = [
            'innings' => [
                ['user_id' => $players[0], 'runs' => [], 'total' => 0, 'balls_faced' => 0, 'is_done' => false],
                ['user_id' => $players[1], 'runs' => [], 'total' => 0, 'balls_faced' => 0, 'is_done' => false],
            ],
            'current_innings_index' => 0,
            'current_ball' => 1,
            'max_balls' => 6,
            'status' => 'playing',
            'last_action_at' => now()->timestamp
        ];

        $match->update([
            'status' => 'playing',
            'game_state' => $gameState
        ]);

        // Broadcast event
        broadcast(new Cricket3DMatchUpdated($match->id, $gameState));
    }

    public function submitSwing(Request $request, $code)
    {
        $match = GameMatch::where('room_code', $code)->firstOrFail();
        $user = $request->user();
        $state = $match->game_state;

        if ($state['status'] !== 'playing') {
            return response()->json(['message' => 'Match is not in playing state.'], 422);
        }

        $currentInnings = &$state['innings'][$state['current_innings_index']];

        if ($currentInnings['user_id'] != $user->id) {
            return response()->json(['message' => 'Not your turn to bat.'], 422);
        }

        $timing = $request->input('timing', 0.5);
        
        // Red Line / Out Mechanic: If hit at the very edges (e.g., < 0.08 or > 0.92), user is OUT.
        $isOut = ($timing < 0.08 || $timing > 0.92);
        $runs = $isOut ? 0 : $this->calculateRuns($timing);
        
        // Update current innings
        $currentInnings['runs'][] = $isOut ? 'W' : $runs;
        $currentInnings['total'] += $runs;
        $currentInnings['balls_faced']++;

        // Save last shot result for real-time syncing to both players
        $state['last_shot'] = [
            'user_id' => $user->id,
            'runs' => $runs,
            'is_out' => $isOut,
            'text' => $isOut ? 'OUT' : (string)$runs,
            'timestamp' => now()->timestamp
        ];

        $shouldEndInnings = false;
        
        if ($isOut) {
            $shouldEndInnings = true;
        } elseif ($currentInnings['balls_faced'] >= $state['max_balls']) {
            $shouldEndInnings = true;
        }

        // Early Win Detection (Chase)
        // If it's the second innings and the chaser has already surpassed the target
        if ($state['current_innings_index'] === 1) {
            $target = $state['innings'][0]['total'];
            if ($currentInnings['total'] > $target) {
                $shouldEndInnings = true;
                // Move directly to end game logic
                return $this->handleGameEnd($match, $state);
            }
        }

        if ($shouldEndInnings) {
            $currentInnings['is_done'] = true;
            
            if ($state['current_innings_index'] === 0) {
                // Next player's turn
                $state['current_innings_index'] = 1;
                $state['current_ball'] = 1;
            } else {
                // Both done, determine winner
                return $this->handleGameEnd($match, $state);
            }
        } else {
            $state['current_ball']++;
        }

        $state['last_action_at'] = now()->timestamp;
        $match->update(['game_state' => $state]);
        
        broadcast(new Cricket3DMatchUpdated($match->id, $state));

        return response()->json([
            'match' => $match, 
            'runs' => $runs, 
            'is_out' => $isOut,
            'message' => $isOut ? 'OUT!' : "Scored $runs runs"
        ]);
    }

    private function calculateRuns($timing)
    {
        $diff = abs(0.5 - $timing);

        if ($diff < 0.05) return 6;
        if ($diff < 0.15) return 4;
        if ($diff < 0.25) return 2;
        if ($diff < 0.35) return 1;
        if ($diff > 0.45) return 0; // Out/Dot ball
        
        return 0;
    }

    private function handleGameEnd(GameMatch $match, $state)
    {
        $score1 = $state['innings'][0]['total'];
        $score2 = $state['innings'][1]['total'];

        $state['status'] = 'finished';

        if ($score1 === $score2) {
            // Draw detected
            $result = $this->gameService->handleDraw($match);
            $state['winner_id'] = null;
            $state['is_draw'] = true;
            $state['message'] = "It's a Draw! Bets refunded (10% fee).";
            
            $playerBet = $match->players()->where('user_id', \Auth::id())->value('bet_amount');
            $state['payout'] = $playerBet * 0.9;
        } else {
            // Winner detected
            $winnerId = ($score1 > $score2) ? $state['innings'][0]['user_id'] : $state['innings'][1]['user_id'];
            $winner = \App\Models\User::find($winnerId);
            $result = $this->gameService->finalizeMatch($match, $winner);
            
            $state['winner_id'] = $winnerId;
            $state['payout'] = $result['payout'];
            $state['message'] = "Game Over!";
        }
        
        $match->update(['game_state' => $state]);
        broadcast(new Cricket3DMatchUpdated($match->id, $state));

        return response()->json([
            'match' => $match->load('winner'), 
            'payout' => $state['payout'] ?? 0,
            'message' => $state['message']
        ]);
    }

    public function activeMatch(Request $request)
    {
        $existing = \App\Models\GameMatchPlayer::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->whereHas('match', function ($q) {
                $q->where('game_type', 'cricket3d')
                  ->whereIn('status', ['waiting', 'playing']);
            })->first();

        if ($existing) {
            $match = $existing->match;
            $state = $match->game_state;
            if ($match->status === 'playing' && isset($state['last_action_at']) && isset($state['innings']) && isset($state['current_innings_index'])) {
                $currentInnings = $state['innings'][$state['current_innings_index']];
                if (isset($currentInnings['balls_faced']) && $currentInnings['balls_faced'] == 0) {
                    if (now()->timestamp - $state['last_action_at'] > 10) {
                        $this->gameService->handleTimeout($match);
                        $match->refresh();
                    }
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
            $existing = \App\Models\GameMatchPlayer::where('user_id', $user->id)
                ->where('status', 'active')
                ->whereHas('match', function ($q) {
                    $q->where('game_type', 'cricket3d')
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
            if ($match->players()->count() === 0) {
                $match->delete();
            } else {
                $match->decrement('total_pot', $betAmount);
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
