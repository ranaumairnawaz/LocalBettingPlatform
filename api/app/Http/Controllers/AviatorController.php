<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameLog;
use App\Models\AviatorRound;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AviatorController extends Controller
{
    /**
     * Get the current state of the Aviator game.
     */
    public function getState(Request $request)
    {
        $serverTime = time();
        $roundIndex = (int) floor($serverTime / 60);
        $startTime = $roundIndex * 60;
        $secondsPassed = $serverTime - $startTime;

        // Determine current phase
        if ($secondsPassed < 12) {
            $phase = 'betting';
        } elseif ($secondsPassed < 55) {
            $phase = 'fighting'; // flight phase
        } else {
            $phase = 'cooldown'; // crash reveal phase
        }

        // Fetch Aviator game record
        $game = Game::where('slug', 'aviator')->first();
        if (!$game) {
            return response()->json(['message' => 'Aviator game config not found in database.'], 404);
        }

        // Find or create current round record
        $currentRound = AviatorRound::firstOrCreate(
            ['round_index' => $roundIndex],
            ['settled' => false]
        );

        // Auto-settle older pending rounds
        $unsettledRounds = AviatorRound::where('round_index', '<', $roundIndex)
            ->where('settled', false)
            ->get();

        foreach ($unsettledRounds as $oldRound) {
            $this->settleRound($oldRound, $game);
        }

        // If we are in flight or cooldown phase and current round isn't settled yet, force settle it to lock the multiplier
        if ($secondsPassed >= 12 && !$currentRound->settled) {
            $this->settleRound($currentRound, $game);
            $currentRound->refresh();
        }

        // Load roadmap history (last 15 crash multipliers)
        $history = AviatorRound::where('settled', true)
            ->orderBy('round_index', 'desc')
            ->take(15)
            ->get()
            ->reverse()
            ->values();

        // Calculate totals on current round
        $activeBetsCount = GameLog::where('game_id', $game->id)
            ->where('game_data->round_index', (int) $roundIndex)
            ->count();

        $activeBetsSum = (float) GameLog::where('game_id', $game->id)
            ->where('game_data->round_index', (int) $roundIndex)
            ->sum('bet_amount');

        // Fetch active user's bets for this round
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
                        'amount' => (float) $log->bet_amount,
                        'auto_multiplier' => $log->game_data['auto_multiplier'] ?? null,
                        'cashed_out' => $log->game_data['cashed_out'] ?? false,
                        'cashout_multiplier' => $log->game_data['cashout_multiplier'] ?? null,
                        'status' => $log->status,
                        'result' => $log->result,
                    ];
                });
        }

        return response()->json([
            'server_time' => $serverTime,
            'round_index' => $roundIndex,
            'start_time' => $startTime,
            'seconds_passed' => $secondsPassed,
            'phase' => $phase,
            'min_bet' => (float) $game->min_bet,
            'max_bet' => (float) $game->max_bet,
            'commission_rate' => (float) $game->commission_rate,
            'active_round' => [
                'crash_multiplier' => $currentRound->settled ? (float) $currentRound->crash_multiplier : null,
                'settled' => $currentRound->settled,
            ],
            'totals' => [
                'players_count' => $activeBetsCount,
                'bet_amount' => $activeBetsSum,
            ],
            'user_bets' => $userBets,
            'history' => $history,
        ]);
    }

    /**
     * Place a bet on the upcoming round.
     */
    public function placeBet(Request $request)
    {
        $game = Game::where('slug', 'aviator')->first();
        if (!$game) {
            return response()->json(['message' => 'Aviator game config not found.'], 404);
        }

        $request->validate([
            'amount' => 'required|numeric|min:' . $game->min_bet . '|max:' . $game->max_bet,
            'auto_multiplier' => 'nullable|numeric|min:1.01|max:1000',
        ]);

        $serverTime = time();
        $roundIndex = (int) floor($serverTime / 60);
        $startTime = $roundIndex * 60;
        $secondsPassed = $serverTime - $startTime;

        $targetRoundIndex = $roundIndex;
        $isBooked = false;

        // If betting is closed for this round, book for the next round!
        if ($secondsPassed >= 12) {
            $targetRoundIndex = $roundIndex + 1;
            $isBooked = true;
        }

        $user = $request->user();
        if ($user->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance.'], 422);
        }

        // Ensure round record exists
        AviatorRound::firstOrCreate(
            ['round_index' => $targetRoundIndex],
            ['settled' => false]
        );

        return DB::transaction(function () use ($user, $game, $request, $targetRoundIndex, $isBooked) {
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
                    'round_index' => $targetRoundIndex,
                    'auto_multiplier' => $request->auto_multiplier ? (float) $request->auto_multiplier : null,
                    'cashed_out' => false,
                    'booked' => $isBooked,
                ],
            ]);

            return response()->json([
                'message' => $isBooked ? 'Bet booked for the next round successfully!' : 'Bet placed successfully.',
                'new_balance' => $user->balance,
                'bet' => [
                    'id' => $log->id,
                    'amount' => (float) $log->bet_amount,
                    'auto_multiplier' => $log->game_data['auto_multiplier'] ?? null,
                    'cashed_out' => false,
                    'status' => $log->status,
                    'result' => $log->result,
                    'booked' => $isBooked,
                ]
            ]);
        });
    }

    /**
     * Cash out an active manual bet.
     */
    public function cashout(Request $request)
    {
        $request->validate([
            'bet_log_id' => 'required|integer',
        ]);

        $serverTime = time();
        $roundIndex = (int) floor($serverTime / 60);
        $startTime = $roundIndex * 60;
        $secondsPassed = $serverTime - $startTime;

        // Can only cash out during flight phase
        if ($secondsPassed < 12 || $secondsPassed >= 55) {
            return response()->json(['message' => 'Cannot cash out outside of the flight phase.'], 422);
        }

        $game = Game::where('slug', 'aviator')->first();
        if (!$game) {
            return response()->json(['message' => 'Aviator game config not found.'], 404);
        }

        $user = $request->user();
        $log = GameLog::where('id', $request->bet_log_id)
            ->where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$log) {
            return response()->json(['message' => 'No active pending bet found for this ID.'], 404);
        }

        // Verify the bet corresponds to the active round
        $betRound = $log->game_data['round_index'] ?? null;
        if ((int) $betRound !== $roundIndex) {
            return response()->json(['message' => 'Bet belongs to a different round.'], 422);
        }

        // Calculate actual elapsed flight time
        $flightTime = $serverTime - $startTime - 12;

        // Calculate current multiplier
        $currentMultiplier = 1.00 * exp(0.08 * $flightTime);

        // Fetch decided crash multiplier from current round
        $roundRecord = AviatorRound::where('round_index', $roundIndex)->first();
        if (!$roundRecord || !$roundRecord->settled) {
            return response()->json(['message' => 'Round crash state not settled yet.'], 422);
        }

        $crashMultiplier = (float) $roundRecord->crash_multiplier;

        // Apply a 400ms transit latency grace period for the crash check
        $latencyGraceTime = max(0, $flightTime - 0.4);
        $graceMultiplier = 1.00 * exp(0.08 * $latencyGraceTime);

        // Check if plane has already crashed!
        if ($graceMultiplier >= $crashMultiplier) {
            return response()->json([
                'message' => 'The plane already crashed! Cash out failed.',
                'crashed' => true,
                'crash_multiplier' => $crashMultiplier
            ], 422);
        }

        // Cap the payout multiplier at just under the crash multiplier to be mathematically valid
        $cashoutMultiplier = min($currentMultiplier, $crashMultiplier - 0.01);

        // SUCCESSFUL CASHOUT! Process win
        return DB::transaction(function () use ($user, $game, $log, $cashoutMultiplier, $roundIndex) {
            $payoutAmount = $log->bet_amount * $cashoutMultiplier;
            $commission = $payoutAmount * ($game->commission_rate / 100);
            $netPayout = $payoutAmount - $commission;

            // Increment balance
            $user->increment('balance', $netPayout);

            // Update log
            $log->payout_amount = $netPayout;
            $log->commission_amount = $commission;
            $log->result = 'win';
            $log->status = 'completed';
            $log->game_data = [
                'round_index' => $roundIndex,
                'auto_multiplier' => $log->game_data['auto_multiplier'] ?? null,
                'cashed_out' => true,
                'cashout_multiplier' => $cashoutMultiplier,
            ];
            $log->save();

            return response()->json([
                'message' => 'Cashed out successfully!',
                'multiplier' => $cashoutMultiplier,
                'payout' => $netPayout,
                'new_balance' => $user->balance,
            ]);
        });
    }

    /**
     * Settle an Aviator round using Profit Maximization logic.
     */
    private function settleRound(AviatorRound $round, Game $game)
    {
        if ($round->settled) {
            return;
        }

        // Fetch all pending logs for this round
        $logs = GameLog::where('game_id', $game->id)
            ->where('game_data->round_index', (int) $round->round_index)
            ->where('status', 'pending')
            ->get();

        $totalPool = 0.00;
        foreach ($logs as $log) {
            $totalPool += (float) $log->bet_amount;
        }

        // Define candidate multipliers to evaluate
        $candidates = [1.00, 1.10, 1.20, 1.30, 1.50, 1.80, 2.00, 2.50, 3.00, 4.00, 5.00, 10.00, 20.00, 50.00, 100.00];

        $bestMultiplier = 1.00;
        $maxProfit = -999999999.00;
        $profitableCandidates = [];

        foreach ($candidates as $m) {
            $liability = 0.00;

            foreach ($logs as $log) {
                $autoMult = $log->game_data['auto_multiplier'] ?? null;

                if ($autoMult !== null) {
                    // If candidate >= auto-multiplier, player wins automatically
                    if ($m >= $autoMult) {
                        $liability += (float) $log->bet_amount * $autoMult;
                    }
                } else {
                    // Manual cashout: model liability conservatively as cashing out right at the crash point
                    $liability += (float) $log->bet_amount * $m;
                }
            }

            $profit = $totalPool - $liability;

            // Track standard maximum profit candidate
            if ($profit > $maxProfit) {
                $maxProfit = $profit;
                $bestMultiplier = $m;
            }

            // Track any candidate that guarantees a net positive profit margin (e.g. >= 10% of total pool)
            if ($totalPool > 0 && $profit >= ($totalPool * 0.10)) {
                $profitableCandidates[] = [
                    'multiplier' => $m,
                    'profit' => $profit
                ];
            }
        }

        // If we have candidates that guarantee house profit, pick the highest multiplier among them
        // to make the game visually exciting and look fair/generous to the players!
        if (count($profitableCandidates) > 0) {
            usort($profitableCandidates, function ($a, $b) {
                return $b['multiplier'] <=> $a['multiplier']; // sort descending
            });
            $bestMultiplier = $profitableCandidates[0]['multiplier'];
        }

        // Add organic micro-variation to make settled multipliers look natural and authentic
        if ($bestMultiplier > 1.00) {
            mt_srand($round->round_index);
            $variation = mt_rand(0, 9) / 100; // 0.00 to 0.09
            $bestMultiplier += $variation;
            mt_srand();
        }

        // If no bets were placed, choose a highly exciting random multiplier using a round seed!
        if ($totalPool <= 0) {
            mt_srand($round->round_index);
            $rand = mt_rand(5, 1000) / 1000; // avoid division by zero or extremely high bounds, min multiplier 1.00x
            $bestMultiplier = 1.00 + (0.98 / $rand) - 0.98;
            $bestMultiplier = round(max(1.00, min(150.00, $bestMultiplier)), 2);
            mt_srand();
        }

        // Save round crash results
        $round->crash_multiplier = $bestMultiplier;
        $round->settled = true;
        $round->save();

        // Process all bets for this round inside a secure database transaction
        DB::transaction(function () use ($logs, $bestMultiplier, $game) {
            foreach ($logs as $log) {
                $autoMult = $log->game_data['auto_multiplier'] ?? null;

                // A pending bet wins only if they set an auto-multiplier and it was reached
                if ($autoMult !== null && $bestMultiplier >= $autoMult) {
                    $payoutAmount = $log->bet_amount * $autoMult;
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
                    $log->game_data = [
                        'round_index' => (int) $log->game_data['round_index'],
                        'auto_multiplier' => $autoMult,
                        'cashed_out' => true,
                        'cashout_multiplier' => $autoMult,
                    ];
                    $log->save();
                } else {
                    // Manual bets that weren't cashed out, or auto-bets that crashed beforehand
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
