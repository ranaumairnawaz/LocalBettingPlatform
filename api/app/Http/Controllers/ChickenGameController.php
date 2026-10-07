<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\GameLog;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;

class ChickenGameController extends Controller
{
    private $multipliers = [
        1.15, 1.25, 1.38, 1.52, 1.68, 1.86, 2.05, 2.28, 2.54, 2.85,
        3.20, 3.60, 4.10, 4.70, 5.45, 6.35, 7.45, 8.85, 10.60, 12.85,
        15.85, 20.00, 30.00, 50.00
    ];
    private $rtp = 0.90; // True 90% RTP

    public function start(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:10']);

        $user = $request->user();
        $game = Game::where('name', 'like', '%Chicken%')->first();
        if (!$game) { $game = Game::find(3); }

        if ($user->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance.'], 422);
        }

        return DB::transaction(function () use ($user, $game, $request) {
            $user->decrement('balance', $request->amount);

            $jackpotContribution = $request->amount * 0.01;
            $jackpotSetting = SystemSetting::firstOrCreate(['key' => 'chicken_mega_jackpot'], ['value' => 0]);
            $jackpotSetting->increment('value', $jackpotContribution);

            $log = GameLog::create([
                'user_id' => $user->id,
                'game_id' => $game->id,
                'bet_amount' => $request->amount,
                'status' => 'pending',
                'result' => 'pending',
                'game_data' => ['current_lane' => 0],
            ]);

            return response()->json([
                'message' => 'Game started.',
                'log_id' => $log->id,
                'new_balance' => $user->balance,
                'jackpot' => $jackpotSetting->value
            ]);
        });
    }

    public function move(Request $request)
    {
        $request->validate(['log_id' => 'required|exists:game_logs,id']);

        $user = $request->user();
        $log = GameLog::where('id', $request->log_id)->where('user_id', $user->id)->firstOrFail();

        if ($log->status !== 'pending') {
            return response()->json(['message' => 'Game already finished.'], 422);
        }

        $gameData = $log->game_data;
        $currentLane = $gameData['current_lane'];

        if ($currentLane >= count($this->multipliers)) {
            return response()->json(['message' => 'Already crossed.'], 422);
        }

        $targetMultiplier = $this->multipliers[$currentLane];
        $activeRtp = $this->rtp;

        // 1. High Roller Penalty (bet > 7000)
        if ($log->bet_amount >= 7000) {
            $activeRtp = 0.40; // 40% RTP for huge bets (60% house edge)
        }

        $targetP = $activeRtp / $targetMultiplier;
        $previousP = 1.0;
        
        if ($currentLane > 0) {
            $previousMultiplier = $this->multipliers[$currentLane - 1];
            $previousP = $activeRtp / $previousMultiplier;
        }

        $passChance = $targetP / $previousP;

        // 2. Force loss on lane 1 (~35% of the time)
        if ($currentLane === 0 && $passChance > 0.65) {
            $passChance = 0.65;
        }

        // 3. Restrict deep progression (only ~1 in 10 reach past step 3)
        if ($currentLane >= 2) {
            $passChance = $passChance * 0.5; // Cut progression chance in half for later stages
        }

        if ((mt_rand() / mt_getrandmax()) < $passChance) {
            $gameData['current_lane'] = $currentLane + 1;
            $log->update(['game_data' => $gameData]);
            
            return response()->json([
                'status' => 'success',
                'current_lane' => $gameData['current_lane'],
                'current_multiplier' => $targetMultiplier
            ]);
        } else {
            $log->update(['status' => 'completed', 'result' => 'lose', 'payout_amount' => 0]);
            return response()->json(['status' => 'crash', 'current_lane' => $currentLane]);
        }
    }

    public function cashout(Request $request)
    {
        $request->validate(['log_id' => 'required|exists:game_logs,id']);

        $user = $request->user();
        $log = GameLog::where('id', $request->log_id)->where('user_id', $user->id)->firstOrFail();

        if ($log->status !== 'pending') {
            return response()->json(['message' => 'Game already finished.'], 422);
        }

        $gameData = $log->game_data;
        $currentLane = $gameData['current_lane'];

        if ($currentLane === 0) {
            return response()->json(['message' => 'Cannot cashout at start.'], 422);
        }

        return DB::transaction(function () use ($log, $user, $currentLane) {
            $multiplier = $this->multipliers[$currentLane - 1];
            $payout = $log->bet_amount * $multiplier;
            
            $feePercentage = SystemSetting::where('key', 'chicken_platform_fee')->value('value') ?? 5;
            $commission = $payout * ($feePercentage / 100);
            $netPayout = $payout - $commission;

            $jackpotWon = 0;
            if ($currentLane == count($this->multipliers)) {
                $jackpotSetting = SystemSetting::where('key', 'chicken_mega_jackpot')->lockForUpdate()->first();
                if ($jackpotSetting && $jackpotSetting->value > 0) {
                    $jackpotWon = $jackpotSetting->value * 0.10; 
                    $jackpotSetting->decrement('value', $jackpotWon);
                    $netPayout += $jackpotWon;
                }
            }

            $user->increment('balance', $netPayout);

            $log->update([
                'status' => 'completed',
                'result' => 'win',
                'payout_amount' => $netPayout,
                'commission_amount' => $commission,
            ]);

            return response()->json([
                'status' => 'cashed_out',
                'payout' => $netPayout,
                'jackpot_won' => $jackpotWon,
                'new_balance' => $user->balance
            ]);
        });
    }

    public function getJackpot()
    {
        $jackpotSetting = SystemSetting::where('key', 'chicken_mega_jackpot')->first();
        return response()->json(['jackpot' => $jackpotSetting ? $jackpotSetting->value : 0]);
    }
}
