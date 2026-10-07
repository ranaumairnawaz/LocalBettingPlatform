<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameLog;
use App\Models\ChickenFightRound;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChickenFightController extends Controller
{
    /**
     * Get the current state of the live game.
     */
    public function getState(Request $request)
    {
        $serverTime = time();
        $roundIndex = (int) floor($serverTime / 60);
        $startTime = $roundIndex * 60;
        $secondsPassed = $serverTime - $startTime;

        // Determine current phase
        if ($secondsPassed < 10) {
            $phase = 'cooldown';
        } elseif ($secondsPassed < 30) {
            $phase = 'betting';
        } else {
            $phase = 'fighting';
        }

        // Fetch the Game record for chicken_fight
        $game = Game::where('slug', 'chicken_fight')->first();
        if (!$game) {
            return response()->json(['message' => 'Chicken Fight game not found in database.'], 404);
        }

        // Find or create current round
        $currentRound = ChickenFightRound::firstOrCreate(
            ['round_index' => $roundIndex],
            ['settled' => false]
        );

        // Auto-settle any older pending rounds
        $unsettledRounds = ChickenFightRound::where('round_index', '<', $roundIndex)
            ->where('settled', false)
            ->get();

        foreach ($unsettledRounds as $oldRound) {
            $this->settleRound($oldRound, $game);
        }

        // Fetch roadmap history (last 15 settled rounds)
        $history = ChickenFightRound::where('settled', true)
            ->orderBy('round_index', 'desc')
            ->take(15)
            ->get()
            ->reverse()
            ->values();

        // Calculate total bets on current round per choice
        $choiceTotals = [
            'white' => 0.00,
            'black' => 0.00,
            'white_over' => 0.00,
            'white_under' => 0.00,
            'black_over' => 0.00,
            'black_under' => 0.00,
        ];

        $currentRoundLogs = GameLog::where('game_id', $game->id)
            ->where('game_data->round_index', (int) $roundIndex)
            ->get();

        foreach ($currentRoundLogs as $log) {
            $choice = $log->game_data['choice'] ?? null;
            if ($choice && isset($choiceTotals[$choice])) {
                $choiceTotals[$choice] += (float) $log->bet_amount;
            }
        }

        // Fetch active user's bets on this round
        $user = $request->user();
        $userBets = [];
        if ($user) {
            $userBets = GameLog::where('game_id', $game->id)
                ->where('user_id', $user->id)
                ->where('game_data->round_index', (int) $roundIndex)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'choice' => $log->game_data['choice'] ?? null,
                        'amount' => $log->bet_amount,
                        'status' => $log->status,
                        'result' => $log->result,
                    ];
                });
        }

        // Get winner result for previous round to show in cooldown/betting panel
        $previousRound = ChickenFightRound::where('round_index', $roundIndex - 1)
            ->where('settled', true)
            ->first();

        // If active phase is fighting and the current round has already been settled (due to state check),
        // we can fetch the decided winner of this round as well
        $activeWinner = null;
        $activeWinnerHp = null;
        if ($phase === 'fighting') {
            // Force settle the current round if we are in the fight phase and it's not settled yet
            if (!$currentRound->settled) {
                $this->settleRound($currentRound, $game);
                $currentRound->refresh();
            }
            $activeWinner = $currentRound->winner;
            $activeWinnerHp = $currentRound->winner_hp;
        }

        return response()->json([
            'server_time' => $serverTime,
            'round_index' => $roundIndex,
            'start_time' => $startTime,
            'seconds_passed' => $secondsPassed,
            'phase' => $phase,
            'min_bet' => $game->min_bet,
            'max_bet' => $game->max_bet,
            'commission_rate' => $game->commission_rate,
            'previous_round' => $previousRound ? [
                'winner' => $previousRound->winner,
                'winner_hp' => $previousRound->winner_hp,
            ] : null,
            'active_round' => [
                'winner' => $activeWinner,
                'winner_hp' => $activeWinnerHp,
            ],
            'totals' => $choiceTotals,
            'user_bets' => $userBets,
            'history' => $history,
        ]);
    }

    /**
     * Place a bet on the current active round.
     */
    public function placeBet(Request $request)
    {
        $game = Game::where('slug', 'chicken_fight')->first();
        if (!$game) {
            return response()->json(['message' => 'Chicken Fight game not found.'], 404);
        }

        $request->validate([
            'choice' => 'required|string|in:white,black,white_over,white_under,black_over,black_under',
            'amount' => 'required|numeric|min:' . $game->min_bet . '|max:' . $game->max_bet,
        ]);

        $serverTime = time();
        $roundIndex = (int) floor($serverTime / 60);
        $startTime = $roundIndex * 60;
        $secondsPassed = $serverTime - $startTime;

        // Check if betting is allowed (only between 10s and 30s)
        if ($secondsPassed < 10 || $secondsPassed >= 30) {
            return response()->json(['message' => 'Betting is closed for this round. Please wait for the next round.'], 422);
        }

        $user = $request->user();
        if ($user->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance.'], 422);
        }

        // Ensure the round model exists
        ChickenFightRound::firstOrCreate(
            ['round_index' => $roundIndex],
            ['settled' => false]
        );

        return DB::transaction(function () use ($user, $game, $request, $roundIndex) {
            // Decrement balance
            $user->decrement('balance', $request->amount);

            // Log bet
            $log = GameLog::create([
                'user_id' => $user->id,
                'game_id' => $game->id,
                'bet_amount' => $request->amount,
                'status' => 'pending',
                'result' => 'pending',
                'game_data' => [
                    'choice' => $request->choice,
                    'round_index' => $roundIndex,
                ],
            ]);

            return response()->json([
                'message' => 'Bet placed successfully.',
                'new_balance' => $user->balance,
                'bet' => [
                    'id' => $log->id,
                    'choice' => $request->choice,
                    'amount' => $log->bet_amount,
                    'status' => $log->status,
                    'result' => $log->result,
                ]
            ]);
        });
    }

    /**
     * Settle a specific chicken fight round to maximize platform profit.
     */
    private function settleRound(ChickenFightRound $round, Game $game)
    {
        if ($round->settled) {
            return;
        }

        // Fetch all pending logs for this round
        $logs = GameLog::where('game_id', $game->id)
            ->where('game_data->round_index', (int) $round->round_index)
            ->where('status', 'pending')
            ->get();

        $totalWhite = 0.00;
        $totalBlack = 0.00;
        $totalWhiteOver = 0.00;
        $totalWhiteUnder = 0.00;
        $totalBlackOver = 0.00;
        $totalBlackUnder = 0.00;

        foreach ($logs as $log) {
            $choice = $log->game_data['choice'] ?? '';
            $amt = (float) $log->bet_amount;

            if ($choice === 'white') $totalWhite += $amt;
            elseif ($choice === 'black') $totalBlack += $amt;
            elseif ($choice === 'white_over') $totalWhiteOver += $amt;
            elseif ($choice === 'white_under') $totalWhiteUnder += $amt;
            elseif ($choice === 'black_over') $totalBlackOver += $amt;
            elseif ($choice === 'black_under') $totalBlackUnder += $amt;
        }

        // Payout multipliers: Main choice = 1.95x, HP sub-choice = 3.80x
        $payouts = [
            'white_over' => ($totalWhite * 1.95) + ($totalWhiteOver * 3.80),
            'white_under' => ($totalWhite * 1.95) + ($totalWhiteUnder * 3.80),
            'black_over' => ($totalBlack * 1.95) + ($totalBlackOver * 3.80),
            'black_under' => ($totalBlack * 1.95) + ($totalBlackUnder * 3.80),
        ];

        // Find the outcome that has the minimum payout for the platform (highest platform profit)
        $minPayout = min($payouts);
        $bestOutcomes = array_keys($payouts, $minPayout);

        // Deterministically pick one based on the round_index so it is consistent
        mt_srand($round->round_index);
        $chosenOutcome = $bestOutcomes[mt_rand(0, count($bestOutcomes) - 1)];
        mt_srand(); // reset seed

        $parts = explode('_', $chosenOutcome);
        $winner = $parts[0];   // 'white' or 'black'
        $winnerHp = $parts[1]; // 'over' or 'under'

        // Save round results
        $round->update([
            'winner' => $winner,
            'winner_hp' => $winnerHp,
            'settled' => true,
        ]);

        // Process all bets for this round in a transaction
        DB::transaction(function () use ($logs, $winner, $winnerHp, $game) {
            foreach ($logs as $log) {
                $choice = $log->game_data['choice'] ?? '';
                $isWin = false;
                $multiplier = 0.00;

                if ($choice === 'white' && $winner === 'white') {
                    $isWin = true;
                    $multiplier = 1.95;
                } elseif ($choice === 'black' && $winner === 'black') {
                    $isWin = true;
                    $multiplier = 1.95;
                } elseif ($choice === 'white_over' && $winner === 'white' && $winnerHp === 'over') {
                    $isWin = true;
                    $multiplier = 3.80;
                } elseif ($choice === 'white_under' && $winner === 'white' && $winnerHp === 'under') {
                    $isWin = true;
                    $multiplier = 3.80;
                } elseif ($choice === 'black_over' && $winner === 'black' && $winnerHp === 'over') {
                    $isWin = true;
                    $multiplier = 3.80;
                } elseif ($choice === 'black_under' && $winner === 'black' && $winnerHp === 'under') {
                    $isWin = true;
                    $multiplier = 3.80;
                }

                if ($isWin) {
                    $payoutAmount = $log->bet_amount * $multiplier;
                    $commission = $payoutAmount * ($game->commission_rate / 100);
                    $netPayout = $payoutAmount - $commission;

                    // Increment winner's balance
                    $user = User::find($log->user_id);
                    if ($user) {
                        $user->increment('balance', $netPayout);
                    }

                    $log->payout_amount = $netPayout;
                    $log->commission_amount = $commission;
                    $log->result = 'win';
                    $log->status = 'completed';
                    $log->save();
                } else {
                    $log->payout_amount = 0.00;
                    $log->commission_amount = 0.00;
                    $log->result = 'lose';
                    $log->status = 'completed';
                    $log->save();
                }
            }
        });
    }
}
