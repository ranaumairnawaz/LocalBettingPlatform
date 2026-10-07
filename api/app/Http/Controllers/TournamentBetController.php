<?php

namespace App\Http\Controllers;

use App\Models\TournamentBet;
use App\Models\TournamentMatch;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TournamentBetController extends Controller
{
    public function placeBet(Request $request, TournamentMatch $match)
    {
        $request->validate([
            'predicted_winner_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($match->status !== 'pending' && $match->status !== 'scheduled') {
            return response()->json(['error' => 'Match has already started or ended.'], 400);
        }

        if ($match->player1_id !== $request->predicted_winner_id && $match->player2_id !== $request->predicted_winner_id) {
            return response()->json(['error' => 'Predicted winner is not in this match.'], 400);
        }

        $user = $request->user();

        if ($user->balance < $request->amount) {
            return response()->json(['error' => 'Insufficient balance.'], 400);
        }

        // Determine dynamic odds (for simplicity, we use fixed odds 2.0 or scale based on rank/points in a real scenario)
        $odds = 2.00;

        $bet = DB::transaction(function () use ($user, $match, $request, $odds) {
            $user->decrement('balance', $request->amount);

            Transaction::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'type' => 'bet_placed',
                'status' => 'completed',
                'description' => "Placed bet on match {$match->id}",
            ]);

            return TournamentBet::create([
                'user_id' => $user->id,
                'tournament_match_id' => $match->id,
                'predicted_winner_id' => $request->predicted_winner_id,
                'amount' => $request->amount,
                'odds' => $odds,
                'status' => 'pending',
            ]);
        });

        return response()->json(['message' => 'Bet placed successfully.', 'bet' => $bet], 201);
    }

    public function userBets(Request $request)
    {
        $bets = TournamentBet::with(['match.tournament.game', 'predictedWinner'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();
        return response()->json(['bets' => $bets]);
    }
}
