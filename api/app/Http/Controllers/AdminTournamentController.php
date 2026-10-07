<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\TournamentMatch;
use App\Models\TournamentPrizeDistribution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminTournamentController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $totalTournaments = Tournament::count();
        $activeTournaments = Tournament::whereIn('status', ['open', 'registration_closed', 'in_progress'])->count();
        $completedTournaments = Tournament::where('status', 'completed')->count();
        $cancelledTournaments = Tournament::where('status', 'cancelled')->count();

        $totalRevenue = Tournament::sum('total_pool');
        $totalPrizesDistributed = Tournament::sum('total_distributed_prizes');
        $totalHouseProfit = Tournament::sum('house_profit');
        $totalPlatformCommission = Tournament::get()->sum(function ($t) {
            return $t->calculatePlatformCommission();
        });

        $totalRegistrations = TournamentRegistration::count();
        $totalPlayers = TournamentRegistration::distinct('user_id')->count();

        return response()->json([
            'overview' => [
                'total_tournaments' => $totalTournaments,
                'active_tournaments' => $activeTournaments,
                'completed_tournaments' => $completedTournaments,
                'cancelled_tournaments' => $cancelledTournaments,
            ],
            'financials' => [
                'total_revenue' => $totalRevenue,
                'total_prizes_distributed' => $totalPrizesDistributed,
                'total_house_profit' => $totalHouseProfit,
                'total_platform_commission' => $totalPlatformCommission,
                'profit_margin_percentage' => $totalRevenue > 0 ? ($totalHouseProfit / $totalRevenue) * 100 : 0,
            ],
            'participation' => [
                'total_registrations' => $totalRegistrations,
                'total_unique_players' => $totalPlayers,
                'average_registrations_per_tournament' => $totalTournaments > 0 ? $totalRegistrations / $totalTournaments : 0,
            ],
        ]);
    }

    public function audit(Tournament $tournament): JsonResponse
    {
        $tournament->load(['registrations', 'matches', 'prizeDistributions']);

        $totalPool = $tournament->total_pool;
        $platformCommission = $tournament->calculatePlatformCommission();
        $prizePool = $tournament->calculatePrizePool();
        $totalDistributedPrizes = $tournament->prizeDistributions->sum('prize_amount');
        $houseProfit = $tournament->house_profit;

        $registrations = $tournament->registrations->map(function ($reg) {
            return [
                'user_id' => $reg->user_id,
                'user_name' => $reg->user->name,
                'entry_fee_paid' => $reg->entry_fee_paid,
                'registered_at' => $reg->registered_at,
                'bracket_position' => $reg->bracket_position,
                'received_bye' => $reg->received_bye,
                'final_rank' => $reg->final_rank,
                'prize_won' => $reg->prize_won,
                'status' => $reg->status,
            ];
        });

        $matches = $tournament->matches->groupBy('round_number')->map(function ($roundMatches) {
            return [
                'round' => $roundMatches->first()->round_number,
                'matches' => $roundMatches->map(function ($match) {
                    return [
                        'match_number' => $match->match_number,
                        'player1' => $match->player1 ? $match->player1->name : 'BYE',
                        'player2' => $match->player2 ? $match->player2->name : 'BYE',
                        'winner' => $match->winner ? $match->winner->name : null,
                        'status' => $match->status,
                        'result_type' => $match->result_type,
                        'started_at' => $match->started_at,
                        'completed_at' => $match->completed_at,
                    ];
                }),
            ];
        });

        $prizeBreakdown = [
            'winner' => [
                'percentage' => $tournament->winner_prize_percentage,
                'amount' => $prizePool * ($tournament->winner_prize_percentage / 100),
            ],
            'runner_up' => [
                'percentage' => $tournament->runner_up_prize_percentage,
                'amount' => $prizePool * ($tournament->runner_up_prize_percentage / 100),
            ],
        ];

        if ($tournament->include_semifinalist_prizes && $tournament->semifinalist_prize_percentage) {
            $prizeBreakdown['semifinalists'] = [
                'percentage' => $tournament->semifinalist_prize_percentage,
                'amount' => $prizePool * ($tournament->semifinalist_prize_percentage / 100),
                'per_player' => ($prizePool * ($tournament->semifinalist_prize_percentage / 100)) / 2,
            ];
        }

        return response()->json([
            'tournament' => [
                'id' => $tournament->id,
                'title' => $tournament->title,
                'game' => $tournament->game->name ?? $tournament->game->title,
                'status' => $tournament->status,
                'entry_fee' => $tournament->entry_fee,
                'registered_players_count' => $tournament->registered_players_count,
                'registration_opens_at' => $tournament->registration_opens_at,
                'registration_closes_at' => $tournament->registration_closes_at,
                'starts_at' => $tournament->starts_at,
                'ends_at' => $tournament->ends_at,
            ],
            'financial_audit' => [
                'total_pool' => $totalPool,
                'platform_commission' => [
                    'percentage' => $tournament->platform_commission_percentage,
                    'amount' => $platformCommission,
                ],
                'prize_pool' => $prizePool,
                'total_distributed_prizes' => $totalDistributedPrizes,
                'remaining_prize_pool' => $prizePool - $totalDistributedPrizes,
                'house_profit' => [
                    'platform_commission' => $platformCommission,
                    'remaining_pool' => $totalPool - $platformCommission - $totalDistributedPrizes,
                    'total' => $houseProfit,
                ],
                'profit_margin_percentage' => $totalPool > 0 ? ($houseProfit / $totalPool) * 100 : 0,
            ],
            'prize_breakdown' => $prizeBreakdown,
            'registrations' => $registrations,
            'matches' => $matches,
            'prize_distributions' => $tournament->prizeDistributions->map(function ($dist) {
                return [
                    'rank' => $dist->rank,
                    'rank_label' => $dist->rank_label,
                    'user_name' => $dist->user->name,
                    'prize_amount' => $dist->prize_amount,
                    'percentage_of_pool' => $dist->percentage_of_pool,
                    'distributed_at' => $dist->distributed_at,
                ];
            }),
        ]);
    }

    public function tournamentsList(Request $request): JsonResponse
    {
        $query = Tournament::with(['game']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        $tournaments = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        return response()->json([
            'tournaments' => $tournaments->items(),
            'pagination' => [
                'current_page' => $tournaments->currentPage(),
                'last_page' => $tournaments->lastPage(),
                'per_page' => $tournaments->perPage(),
                'total' => $tournaments->total(),
            ],
        ]);
    }

    public function matchDetails(TournamentMatch $match): JsonResponse
    {
        $match->load(['tournament', 'player1', 'player2', 'winner']);

        return response()->json([
            'match' => [
                'id' => $match->id,
                'tournament' => [
                    'id' => $match->tournament->id,
                    'title' => $match->tournament->title,
                ],
                'round_number' => $match->round_number,
                'match_number' => $match->match_number,
                'player1' => $match->player1 ? [
                    'id' => $match->player1->id,
                    'name' => $match->player1->name,
                    'avatar' => $match->player1->avatar,
                ] : null,
                'player2' => $match->player2 ? [
                    'id' => $match->player2->id,
                    'name' => $match->player2->name,
                    'avatar' => $match->player2->avatar,
                ] : null,
                'winner' => $match->winner ? [
                    'id' => $match->winner->id,
                    'name' => $match->winner->name,
                ] : null,
                'status' => $match->status,
                'scheduled_at' => $match->scheduled_at,
                'started_at' => $match->started_at,
                'completed_at' => $match->completed_at,
                'player1_ready' => $match->player1_ready,
                'player2_ready' => $match->player2_ready,
                'ready_check_started_at' => $match->ready_check_started_at,
                'game_room_id' => $match->game_room_id,
                'player1_metrics' => $match->player1_metrics,
                'player2_metrics' => $match->player2_metrics,
                'result_type' => $match->result_type,
            ],
        ]);
    }

    public function updateMatchResult(Request $request, TournamentMatch $match): JsonResponse
    {
        $validated = $request->validate([
            'winner_id' => 'required|exists:users,id',
            'result_type' => 'nullable|in:normal,forfeit,walkover,double_disconnect,random',
            'player1_metrics' => 'nullable|array',
            'player2_metrics' => 'nullable|array',
        ]);

        $match->update($validated);

        return response()->json([
            'match' => $match->fresh()->load(['player1', 'player2', 'winner']),
        ]);
    }

    public function forceAdvanceMatch(TournamentMatch $match, int $winnerUserId): JsonResponse
    {
        $bracketGenerator = new \App\Services\BracketGenerator();
        $bracketGenerator->advanceWinner($match, $winnerUserId);

        return response()->json([
            'message' => 'Match advanced successfully',
            'match' => $match->fresh()->load(['player1', 'player2', 'winner']),
        ]);
    }
}
