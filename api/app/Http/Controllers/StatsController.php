<?php

namespace App\Http\Controllers;

use App\Models\GameLog;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function getStats(Request $request)
    {
        $userId = Auth::id();

        // 1. Total Deposits
        $totalDeposits = (float) Transaction::where('user_id', $userId)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');

        // 2. Total Withdrawals
        $totalWithdrawals = (float) Transaction::where('user_id', $userId)
            ->where('type', 'withdrawal')
            ->where('status', 'completed')
            ->sum('amount');

        // 3. Game Log aggregates
        $totalWinnings = (float) GameLog::where('user_id', $userId)
            ->where('result', 'win')
            ->sum('payout_amount');

        $totalLosses = (float) GameLog::where('user_id', $userId)
            ->where('result', 'lose')
            ->sum('bet_amount');

        $winsCount = GameLog::where('user_id', $userId)
            ->where('result', 'win')
            ->count();

        $lossesCount = GameLog::where('user_id', $userId)
            ->where('result', 'lose')
            ->count();

        $totalGames = $winsCount + $lossesCount;
        $winPercentage = $totalGames > 0 ? round(($winsCount / $totalGames) * 100, 2) : 0;

        // 4. Best Game (game with most wins)
        $bestGameData = GameLog::where('user_id', $userId)
            ->where('result', 'win')
            ->select('game_id', DB::raw('count(*) as wins_count'))
            ->groupBy('game_id')
            ->orderBy('wins_count', 'desc')
            ->with('game')
            ->first();

        $bestGame = null;
        if ($bestGameData && $bestGameData->game) {
            $bestGame = [
                'name' => $bestGameData->game->name,
                'slug' => $bestGameData->game->slug,
                'wins' => $bestGameData->wins_count,
                'image' => $bestGameData->game->image
            ];
        }

        // 5. Worst Game (game with most losses)
        $worstGameData = GameLog::where('user_id', $userId)
            ->where('result', 'lose')
            ->select('game_id', DB::raw('count(*) as losses_count'))
            ->groupBy('game_id')
            ->orderBy('losses_count', 'desc')
            ->with('game')
            ->first();

        $worstGame = null;
        if ($worstGameData && $worstGameData->game) {
            $worstGame = [
                'name' => $worstGameData->game->name,
                'slug' => $worstGameData->game->slug,
                'losses' => $worstGameData->losses_count,
                'image' => $worstGameData->game->image
            ];
        }

        return response()->json([
            'total_deposits' => $totalDeposits,
            'total_withdrawals' => $totalWithdrawals,
            'total_winnings' => $totalWinnings,
            'total_losses' => $totalLosses,
            'total_games' => $totalGames,
            'wins_count' => $winsCount,
            'losses_count' => $lossesCount,
            'win_percentage' => $winPercentage,
            'best_game' => $bestGame,
            'worst_game' => $worstGame,
        ]);
    }
}
