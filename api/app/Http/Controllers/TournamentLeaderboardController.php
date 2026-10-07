<?php

namespace App\Http\Controllers;

use App\Models\TournamentLeaderboard;
use Illuminate\Http\Request;

class TournamentLeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $seasonId = $request->query('season_id', 1);

        $leaderboard = TournamentLeaderboard::with('user:id,name,email')
            ->where('season_id', $seasonId)
            ->orderByDesc('points')
            ->orderByDesc('tournaments_won')
            ->orderByDesc('matches_won')
            ->take(100)
            ->get();

        return response()->json([
            'season_id' => $seasonId,
            'leaderboard' => $leaderboard,
        ]);
    }
}
