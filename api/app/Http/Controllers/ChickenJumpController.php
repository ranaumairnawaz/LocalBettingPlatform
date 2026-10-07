<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\GameMatchPlayer;
use App\Services\GameService;
use App\Events\ChickenJumpMatchUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChickenJumpController extends Controller
{
    protected $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    // POST /api/chicken-jump/join
    public function join(Request $request)
    {
        $request->validate(['bet_amount' => 'required|numeric|min:10']);

        try {
            $match = $this->gameService->joinMatch(
                $request->user(),
                'chicken_jump',
                $request->bet_amount,
                2
            );

            return response()->json([
                'match'     => $match->load('players.user'),
                'room_code' => $match->room_code,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // GET /api/chicken-jump/active-match
    public function activeMatch(Request $request)
    {
        $existing = GameMatchPlayer::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->whereHas('match', function ($q) {
                $q->where('game_type', 'chicken_jump')
                  ->whereIn('status', ['waiting', 'playing']);
            })->first();

        if ($existing) {
            return response()->json([
                'match'     => $existing->match->load('players.user'),
                'room_code' => $existing->match->room_code,
            ]);
        }

        return response()->json(['match' => null]);
    }

    // GET /api/chicken-jump/match/{code}
    public function getMatch($code)
    {
        $match = GameMatch::where('room_code', $code)
            ->with('players.user')
            ->firstOrFail();

        return response()->json($match);
    }

    // POST /api/chicken-jump/cancel-match
    public function cancelMatch(Request $request)
    {
        $user = $request->user();

        return DB::transaction(function () use ($user) {
            $existing = GameMatchPlayer::where('user_id', $user->id)
                ->where('status', 'active')
                ->whereHas('match', function ($q) {
                    $q->where('game_type', 'chicken_jump')
                      ->where('status', 'waiting');
                })->first();

            if (!$existing) {
                return response()->json(['message' => 'No waiting match found.'], 422);
            }

            $match     = $existing->match;
            $betAmount = $existing->bet_amount;

            $user->increment('balance', $betAmount);
            $existing->delete();

            if ($match->players()->count() === 0) {
                $match->delete();
            } else {
                $match->decrement('total_pot', $betAmount);
            }

            return response()->json([
                'message' => 'Cancelled.',
                'balance' => $user->balance,
            ]);
        });
    }

    // POST /api/chicken-jump/update/{code}
    // Called by the frontend to sync score updates and death events.
    public function updateState(Request $request, $code)
    {
        $request->validate([
            'score' => 'required|integer|min:0',
            'alive' => 'required|boolean',
        ]);

        $match = GameMatch::where('room_code', $code)->firstOrFail();
        $user  = $request->user();

        if ($match->status !== 'playing') {
            return response()->json(['message' => 'Match not active.'], 422);
        }

        $player = $match->players()->where('user_id', $user->id)->first();
        if (!$player) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $state = $match->game_state;

        // Update this player's score and alive status
        foreach ($state['players'] as &$p) {
            if ($p['user_id'] == $user->id) {
                $p['score'] = $request->score;
                $p['alive'] = $request->alive;
                break;
            }
        }
        unset($p);

        $state['last_action_at'] = now()->timestamp;

        // Check if this player just died
        if (!$request->alive) {
            // Find the other player — they win (if still alive)
            $winnerUserId = null;
            foreach ($state['players'] as $p) {
                if ($p['user_id'] != $user->id && $p['alive']) {
                    $winnerUserId = $p['user_id'];
                    break;
                }
            }

            if ($winnerUserId) {
                $winner = \App\Models\User::find($winnerUserId);
                $result = $this->gameService->finalizeMatch($match, $winner);

                $state['status']    = 'finished';
                $state['winner_id'] = $winnerUserId;
                $state['payout']    = $result['payout'];
                $state['message']   = 'Game Over!';

                $match->update(['game_state' => $state]);
                broadcast(new ChickenJumpMatchUpdated($match->id, $state));

                return response()->json([
                    'match'   => $match,
                    'payout'  => $result['payout'],
                    'message' => 'Game Over!',
                ]);
            }
        }

        $match->update(['game_state' => $state]);
        broadcast(new ChickenJumpMatchUpdated($match->id, $state));

        return response()->json(['match' => $match, 'state' => $state]);
    }

    // POST /api/chicken-jump/forfeit/{code}
    public function forfeit(Request $request, $code)
    {
        $match = GameMatch::where('room_code', $code)->firstOrFail();
        $user  = $request->user();

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
