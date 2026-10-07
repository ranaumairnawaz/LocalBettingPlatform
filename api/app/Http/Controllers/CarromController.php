<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\GameMatchPlayer;
use App\Services\GameService;
use App\Events\CarromMatchUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarromController extends Controller
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
            $match = $this->gameService->joinMatch($request->user(), 'carrom', $request->bet_amount, 2);
            
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

        // The user submits the striker placement x, angle, and force
        $x = $request->input('x', 250);
        $angle = $request->input('angle', 0);
        $force = $request->input('force', 10);

        // Record the shot in last_shot
        $state['last_shot'] = [
            'user_id' => $user->id,
            'x' => $x,
            'angle' => $angle,
            'force' => $force,
            'timestamp' => now()->timestamp
        ];

        $state['last_action_at'] = now()->timestamp;
        $match->update(['game_state' => $state]);

        broadcast(new CarromMatchUpdated($match->id, $state));

        return response()->json([
            'match' => $match,
            'message' => 'Striker shot successfully synced.'
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

        // Client passes the updated pucks, player scores, next turn index, or if someone has won
        $pucks = $request->input('pucks');
        $scores = $request->input('scores'); // array of scores matching player order
        $turnIndex = $request->input('next_turn_index');
        $winnerId = $request->input('winner_id');

        if ($pucks) {
            $state['pucks'] = $pucks;
        }

        if ($scores) {
            foreach ($scores as $index => $score) {
                $state['players'][$index]['score'] = $score;
            }
        }

        if ($request->has('extra_turn')) {
            $state['extra_turn'] = $request->input('extra_turn');
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
                broadcast(new CarromMatchUpdated($match->id, $state));

                return response()->json([
                    'match' => $match,
                    'payout' => $result['payout'],
                    'message' => $state['message']
                ]);
            }
        }

        // Switch turns or update turn index
        if (isset($turnIndex)) {
            $state['turn_index'] = $turnIndex;
        }

        $state['last_action_at'] = now()->timestamp;
        $state['last_shot'] = null; // Reset shot state since we updated to final resting position
        $match->update(['game_state' => $state]);

        broadcast(new CarromMatchUpdated($match->id, $state));

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
                $q->where('game_type', 'carrom')
                  ->whereIn('status', ['waiting', 'playing']);
            })->first();

        if ($existing) {
            $match = $existing->match;
            // Auto-check timeout
            $state = $match->game_state;
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
                    $q->where('game_type', 'carrom')
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
