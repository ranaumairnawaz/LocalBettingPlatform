<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    public function index()
    {
        return Game::where('is_active', true)->orderBy('id', 'desc')->get();
    }

    public function launch(Request $request, $slug)
    {
        $game = Game::where('slug', $slug)->where('is_active', true)->firstOrFail();
        
        return response()->json([
            'game' => $game,
            // In production, this would be a signed URL from SPRIBE/Provider
            'url' => "https://demo.spribe.io/launch/aviator?demo=true&lang=en" 
        ]);
    }

    public function bet(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:games,id',
            'amount' => 'required|numeric|min:10',
        ]);

        $user = $request->user();
        $game = Game::find($request->game_id);

        if ($user->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance.'], 422);
        }

        return DB::transaction(function () use ($user, $game, $request) {
            $user->decrement('balance', $request->amount);

            $log = GameLog::create([
                'user_id' => $user->id,
                'game_id' => $game->id,
                'bet_amount' => $request->amount,
                'status' => 'pending',
                'result' => 'pending',
            ]);

            return response()->json([
                'message' => 'Bet placed.',
                'log_id' => $log->id,
                'new_balance' => $user->balance
            ]);
        });
    }

    public function settle(Request $request, $log_id)
    {
        $request->validate([
            'payout' => 'required|numeric', // Gross winning before commission
            'result' => 'required|in:win,lose,draw',
        ]);

        $log = GameLog::findOrFail($log_id);
        $game = $log->game;

        if ($log->status !== 'pending') {
            return response()->json(['message' => 'Already settled.'], 422);
        }

        return DB::transaction(function () use ($log, $game, $request) {
            $commission = 0;
            $net_payout = $request->payout;

            if ($request->result === 'win') {
                // Take 10-15% commission from the winning amount
                $commission = $request->payout * ($game->commission_rate / 100);
                $net_payout = $request->payout - $commission;
                
                $log->user->increment('balance', $net_payout);
            }

            $log->update([
                'payout_amount' => $net_payout,
                'commission_amount' => $commission,
                'result' => $request->result,
                'status' => 'completed',
            ]);

            return response()->json([
                'message' => 'Game settled.',
                'commission_taken' => $commission,
                'net_payout' => $net_payout,
                'new_balance' => $log->user->balance
            ]);
        });
    }

    public function activity(Request $request)
    {
        return $request->user()->gameLogs()->with('game')->orderBy('created_at', 'desc')->get();
    }

    public function leaderboard()
    {
        // Top 20 users by total winnings
        $topUsers = GameLog::select('user_id', DB::raw('SUM(payout_amount) as total_won'))
            ->where('result', 'win')
            ->groupBy('user_id')
            ->orderBy('total_won', 'desc')
            ->take(20)
            ->with('user:id,username')
            ->get();
            
        return response()->json($topUsers);
    }

    public function settings()
    {
        $settings = \App\Models\SystemSetting::pluck('value', 'key');
        return response()->json($settings);
    }
}
