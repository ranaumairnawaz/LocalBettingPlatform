<?php

namespace App\Http\Controllers;

use App\Models\TournamentMatch;
use App\Services\BracketGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TournamentMatchController extends Controller
{
    private BracketGenerator $bracketGenerator;

    public function __construct(BracketGenerator $bracketGenerator)
    {
        $this->bracketGenerator = $bracketGenerator;
    }

    public function setReady(Request $request, TournamentMatch $match): JsonResponse
    {
        $user = $request->user();

        if (!$match->player1_id || !$match->player2_id) {
            return response()->json(['error' => 'Match is not ready yet'], 400);
        }

        if ($match->player1_id !== $user->id && $match->player2_id !== $user->id) {
            return response()->json(['error' => 'You are not a player in this match'], 403);
        }

        if ($match->status !== 'scheduled') {
            return response()->json(['error' => 'Match is not in scheduled status'], 400);
        }

        $match->setPlayerReady($user->id);
        $match->save();

        // Broadcast update
        broadcast(new \App\Events\TournamentMatchUpdate($match));

        // Check if both players are ready
        if ($match->isReadyToStart()) {
            $match->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            // Generate game room
            $gameRoomId = 'tournament-' . $match->tournament_id . '-match-' . $match->id;
            $match->update(['game_room_id' => $gameRoomId]);

            broadcast(new \App\Events\TournamentMatchUpdate($match));
        }

        return response()->json([
            'match' => $match->fresh()->load(['player1', 'player2']),
        ]);
    }

    public function submitResult(Request $request, TournamentMatch $match): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'winner_id' => 'required|exists:users,id',
            'player1_metrics' => 'nullable|array',
            'player2_metrics' => 'nullable|array',
        ]);

        if ($match->status !== 'in_progress') {
            return response()->json(['error' => 'Match is not in progress'], 400);
        }

        if ($match->player1_id !== $user->id && $match->player2_id !== $user->id) {
            return response()->json(['error' => 'You are not a player in this match'], 403);
        }

        $match->update([
            'player1_metrics' => $validated['player1_metrics'] ?? null,
            'player2_metrics' => $validated['player2_metrics'] ?? null,
        ]);

        try {
            $this->bracketGenerator->advanceWinner($match, $validated['winner_id']);

            return response()->json([
                'message' => 'Match result submitted successfully',
                'match' => $match->fresh()->load(['player1', 'player2', 'winner']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function forfeit(Request $request, TournamentMatch $match): JsonResponse
    {
        $user = $request->user();

        if ($match->status !== 'in_progress' && $match->status !== 'scheduled') {
            return response()->json(['error' => 'Match is not active'], 400);
        }

        if ($match->player1_id !== $user->id && $match->player2_id !== $user->id) {
            return response()->json(['error' => 'You are not a player in this match'], 403);
        }

        try {
            $this->bracketGenerator->handleForfeit($match, $user->id);

            return response()->json([
                'message' => 'Forfeit processed successfully',
                'match' => $match->fresh()->load(['player1', 'player2', 'winner']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function reportDisconnect(Request $request, TournamentMatch $match): JsonResponse
    {
        $user = $request->user();

        if ($match->status !== 'in_progress') {
            return response()->json(['error' => 'Match is not in progress'], 400);
        }

        if ($match->player1_id !== $user->id && $match->player2_id !== $user->id) {
            return response()->json(['error' => 'You are not a player in this match'], 403);
        }

        $validated = $request->validate([
            'player_metrics' => 'required|array',
        ]);

        // Update the reporting player's metrics
        if ($match->player1_id === $user->id) {
            $match->update(['player1_metrics' => $validated['player_metrics']]);
        } else {
            $match->update(['player2_metrics' => $validated['player_metrics']]);
        }

        // Check if both players have reported disconnect
        if ($match->player1_metrics && $match->player2_metrics) {
            try {
                $this->bracketGenerator->handleDoubleDisconnect($match);

                return response()->json([
                    'message' => 'Double disconnect processed with tiebreaker',
                    'match' => $match->fresh()->load(['player1', 'player2', 'winner']),
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        return response()->json([
            'message' => 'Disconnect reported. Waiting for opponent report.',
            'match' => $match->fresh(),
        ]);
    }

    public function checkReadyTimeout(TournamentMatch $match): JsonResponse
    {
        if ($match->status !== 'scheduled' || !$match->ready_check_started_at) {
            return response()->json(['error' => 'Match is not in ready check'], 400);
        }

        $elapsed = now()->diffInSeconds($match->ready_check_started_at);
        
        if ($elapsed >= 30) {
            // Check which player didn't respond
            if (!$match->player1_ready && $match->player2_ready) {
                $forfeitingUserId = $match->player1_id;
            } elseif (!$match->player2_ready && $match->player1_ready) {
                $forfeitingUserId = $match->player2_id;
            } else {
                // Both didn't respond - random winner or reschedule
                return response()->json(['error' => 'Both players failed ready check'], 400);
            }

            try {
                $this->bracketGenerator->handleForfeit($match, $forfeitingUserId);

                return response()->json([
                    'message' => 'Ready check timeout processed - player forfeited',
                    'match' => $match->fresh()->load(['player1', 'player2', 'winner']),
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        return response()->json([
            'message' => 'Ready check still active',
            'remaining_seconds' => 30 - $elapsed,
        ]);
    }
}
