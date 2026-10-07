<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\TournamentMatch;
use App\Services\BracketGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TournamentController extends Controller
{
    private BracketGenerator $bracketGenerator;

    public function __construct(BracketGenerator $bracketGenerator)
    {
        $this->bracketGenerator = $bracketGenerator;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Tournament::with(['game', 'registrations.user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        $tournaments = $query->orderBy('registration_closes_at', 'asc')->get();

        return response()->json([
            'tournaments' => $tournaments->map(function ($tournament) use ($request) {
                return [
                    'id' => $tournament->id,
                    'game' => [
                        'id' => $tournament->game->id,
                        'title' => $tournament->game->name ?? $tournament->game->title,
                        'image' => $tournament->game->image,
                    ],
                    'title' => $tournament->title,
                    'description' => $tournament->description,
                    'entry_fee' => $tournament->entry_fee,
                    'registered_players_count' => $tournament->registered_players_count,
                    'max_players' => $tournament->max_players,
                    'min_players' => $tournament->min_players,
                    'remaining_slots' => $tournament->getRemainingSlots(),
                    'time_remaining' => $tournament->time_remaining,
                    'registration_opens_at' => $tournament->registration_opens_at,
                    'registration_closes_at' => $tournament->registration_closes_at,
                    'starts_at' => $tournament->starts_at,
                    'status' => $tournament->status,
                    'is_open_for_registration' => $tournament->isOpenForRegistration(),
                    'is_full' => $tournament->isFull(),
                    'total_pool' => $tournament->calculateTotalPool(),
                    'is_registered' => $tournament->registrations()->where('user_id', $request->user()?->id)->exists(),
                ];
            }),
        ]);
    }

    public function activeTournaments(Request $request): JsonResponse
    {
        $tournaments = Tournament::with(['game'])
            ->whereIn('status', ['open', 'registration_closed', 'in_progress'])
            ->orderBy('registration_closes_at', 'asc')
            ->get();

        return response()->json([
            'tournaments' => $tournaments->map(function ($tournament) use ($request) {
                return [
                    'id' => $tournament->id,
                    'game' => [
                        'id' => $tournament->game->id,
                        'title' => $tournament->game->name ?? $tournament->game->title,
                        'image' => $tournament->game->image,
                        'thumbnail' => $tournament->game->thumbnail ?? null,
                    ],
                    'title' => $tournament->title,
                    'entry_fee' => $tournament->entry_fee,
                    'registered_players_count' => $tournament->registered_players_count,
                    'max_players' => $tournament->max_players,
                    'time_remaining' => $tournament->time_remaining,
                    'registration_closes_at' => $tournament->registration_closes_at,
                    'starts_at' => $tournament->starts_at,
                    'status' => $tournament->status,
                    'is_open_for_registration' => $tournament->isOpenForRegistration(),
                    'is_full' => $tournament->isFull(),
                    'total_pool' => $tournament->calculateTotalPool(),
                    'bracket_type' => $tournament->bracket_type,
                    'is_dynamic_fee' => $tournament->is_dynamic_fee,
                    'base_entry_fee' => $tournament->base_entry_fee,
                    'fee_increment_amount' => $tournament->fee_increment_amount,
                    'fee_increment_every_n_players' => $tournament->fee_increment_every_n_players,
                    'winner_prize_percentage' => $tournament->winner_prize_percentage,
                    'runner_up_prize_percentage' => $tournament->runner_up_prize_percentage,
                    'include_semifinalist_prizes' => $tournament->include_semifinalist_prizes,
                    'semifinalist_prize_percentage' => $tournament->semifinalist_prize_percentage,
                    'platform_commission_percentage' => $tournament->platform_commission_percentage,
                    'is_registered' => $tournament->registrations()->where('user_id', $request->user()?->id)->exists(),
                ];
            }),
        ]);
    }

    public function show(Tournament $tournament, Request $request): JsonResponse
    {
        $tournament->load(['game', 'registrations.user', 'matches.player1', 'matches.player2', 'matches.winner', 'prizeDistributions.user']);

        return response()->json([
            'tournament' => [
                'id' => $tournament->id,
                'game' => [
                    'id' => $tournament->game->id,
                    'title' => $tournament->game->name ?? $tournament->game->title,
                    'image' => $tournament->game->image,
                    'guidelines' => $tournament->game->guidelines,
                ],
                'title' => $tournament->title,
                'description' => $tournament->description,
                'entry_fee' => $tournament->entry_fee,
                'min_players' => $tournament->min_players,
                'max_players' => $tournament->max_players,
                'registration_opens_at' => $tournament->registration_opens_at,
                'registration_closes_at' => $tournament->registration_closes_at,
                'starts_at' => $tournament->starts_at,
                'ends_at' => $tournament->ends_at,
                'winner_prize_percentage' => $tournament->winner_prize_percentage,
                'runner_up_prize_percentage' => $tournament->runner_up_prize_percentage,
                'include_semifinalist_prizes' => $tournament->include_semifinalist_prizes,
                'semifinalist_prize_percentage' => $tournament->semifinalist_prize_percentage,
                'platform_commission_percentage' => $tournament->platform_commission_percentage,
                'status' => $tournament->status,
                'registered_players_count' => $tournament->registered_players_count,
                'total_pool' => $tournament->total_pool,
                'total_distributed_prizes' => $tournament->total_distributed_prizes,
                'house_profit' => $tournament->house_profit,
                'is_open_for_registration' => $tournament->isOpenForRegistration(),
                'is_full' => $tournament->isFull(),
                'remaining_slots' => $tournament->getRemainingSlots(),
                'time_remaining' => $tournament->time_remaining,
                'is_registered' => $tournament->registrations()->where('user_id', $request->user()?->id)->exists(),
                'registrations' => $tournament->registrations->map(function ($reg) {
                    return [
                        'id' => $reg->id,
                        'user' => [
                            'id' => $reg->user->id,
                            'name' => $reg->user->name,
                            'avatar' => $reg->user->avatar,
                        ],
                        'bracket_position' => $reg->bracket_position,
                        'received_bye' => $reg->received_bye,
                        'final_rank' => $reg->final_rank,
                        'prize_won' => $reg->prize_won,
                        'status' => $reg->status,
                    ];
                }),
                'matches' => $tournament->matches->groupBy('round_number')->map(function ($roundMatches) {
                    return $roundMatches->sortBy('match_number')->map(function ($match) {
                        return [
                            'id' => $match->id,
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
                            'game_room_id' => $match->game_room_id,
                        ];
                    })->values();
                }),
                'prize_distributions' => $tournament->prizeDistributions->map(function ($dist) {
                    return [
                        'rank' => $dist->rank,
                        'rank_label' => $dist->rank_label,
                        'user' => [
                            'id' => $dist->user->id,
                            'name' => $dist->user->name,
                        ],
                        'prize_amount' => $dist->prize_amount,
                        'percentage_of_pool' => $dist->percentage_of_pool,
                        'distributed_at' => $dist->distributed_at,
                    ];
                }),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => 'required|exists:games,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'entry_fee' => 'required|numeric|min:0',
            'min_players' => 'required|integer|min:4',
            'max_players' => 'required|integer|min:4',
            'registration_opens_at' => 'required|date',
            'registration_closes_at' => 'required|date|after:registration_opens_at',
            'starts_at' => 'required|date|after:registration_closes_at',
            'winner_prize_percentage' => 'required|numeric|min:0|max:100',
            'runner_up_prize_percentage' => 'required|numeric|min:0|max:100',
            'include_semifinalist_prizes' => 'boolean',
            'semifinalist_prize_percentage' => 'nullable|numeric|min:0|max:100',
            'platform_commission_percentage' => 'required|numeric|min:0|max:100',
            'bracket_type' => 'required|in:single_elimination,double_elimination',
            'is_dynamic_fee' => 'boolean',
            'base_entry_fee' => 'nullable|required_if:is_dynamic_fee,true|numeric|min:0',
            'fee_increment_amount' => 'nullable|required_if:is_dynamic_fee,true|numeric|min:0',
            'fee_increment_every_n_players' => 'nullable|required_if:is_dynamic_fee,true|integer|min:1',
        ]);

        $tournament = Tournament::create($validated);

        return response()->json([
            'tournament' => $tournament->fresh()->load('game'),
        ], 201);
    }

    public function update(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'entry_fee' => 'sometimes|numeric|min:0',
            'min_players' => 'sometimes|integer|min:4',
            'max_players' => 'sometimes|integer|min:4',
            'registration_opens_at' => 'sometimes|date',
            'registration_closes_at' => 'sometimes|date',
            'starts_at' => 'sometimes|date',
            'winner_prize_percentage' => 'sometimes|numeric|min:0|max:100',
            'runner_up_prize_percentage' => 'sometimes|numeric|min:0|max:100',
            'include_semifinalist_prizes' => 'sometimes|boolean',
            'semifinalist_prize_percentage' => 'nullable|numeric|min:0|max:100',
            'platform_commission_percentage' => 'sometimes|numeric|min:0|max:100',
            'status' => ['sometimes', Rule::in(['draft', 'open', 'registration_closed', 'in_progress', 'completed', 'cancelled'])],
            'bracket_type' => 'sometimes|in:single_elimination,double_elimination',
            'is_dynamic_fee' => 'sometimes|boolean',
            'base_entry_fee' => 'nullable|numeric|min:0',
            'fee_increment_amount' => 'nullable|numeric|min:0',
            'fee_increment_every_n_players' => 'nullable|integer|min:1',
        ]);

        $tournament->update($validated);

        return response()->json([
            'tournament' => $tournament->fresh()->load('game'),
        ]);
    }

    public function destroy(Tournament $tournament): JsonResponse
    {
        if ($tournament->status === 'in_progress') {
            return response()->json([
                'error' => 'Cannot delete a tournament that is in progress',
            ], 400);
        }

        // If tournament has registrations, refund all entry fees
        if ($tournament->registrations()->count() > 0) {
            DB::transaction(function () use ($tournament) {
                foreach ($tournament->registrations as $registration) {
                    // Refund entry fee
                    $registration->user->increment('balance', $registration->entry_fee_paid);

                    // Create refund transaction
                    Transaction::create([
                        'user_id' => $registration->user->id,
                        'amount' => $registration->entry_fee_paid,
                        'type' => 'refund',
                        'status' => 'completed',
                        'description' => "Refund for cancelled tournament: {$tournament->title}",
                    ]);
                }
            });
        }

        $tournament->delete();

        return response()->json(null, 204);
    }

    public function register(Request $request, Tournament $tournament): JsonResponse
    {
        $user = $request->user();

        if (!$tournament->isOpenForRegistration()) {
            return response()->json(['error' => 'Tournament is not open for registration'], 400);
        }

        if ($tournament->isFull()) {
            return response()->json(['error' => 'Tournament is full'], 400);
        }

        if ($tournament->registrations()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'You are already registered for this tournament'], 400);
        }

        $entryFee = $tournament->entry_fee;
        if ($tournament->is_dynamic_fee && isset($tournament->base_entry_fee)) {
            $incrementEvery = $tournament->fee_increment_every_n_players ?: 1;
            $increments = floor($tournament->registered_players_count / $incrementEvery);
            $entryFee = $tournament->base_entry_fee + ($increments * ($tournament->fee_increment_amount ?: 0));
        }

        if ($user->balance < $entryFee) {
            return response()->json(['error' => 'Insufficient balance to register'], 400);
        }

        // Wrap in try-catch to expose the exact 500 error cause
        try {
            DB::transaction(function () use ($tournament, $user, $entryFee) {
                $user->decrement('balance', $entryFee);

                // Double check if Transaction model namespace is correct
                \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $entryFee,
                    'type' => 'tournament_entry',
                    'status' => 'completed',
                    'description' => "Entry fee for tournament: {$tournament->title}",
                ]);

                TournamentRegistration::create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $user->id,
                    'entry_fee_paid' => $entryFee,
                    // If this column doesn't exist in your migration, it will trigger the catch block below
                    'registered_at' => now(),
                    'status' => 'registered',
                ]);

                $tournament->increment('registered_players_count');
            });

            return response()->json([
                'message' => 'Successfully registered for tournament',
                'tournament' => $tournament->fresh()->load('game'),
            ], 201);

        } catch (\Exception $e) {
            // This will send the exact error text back to your Vue console log
            return response()->json([
                'error' => 'Backend Crash: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function generateBracket(Tournament $tournament): JsonResponse
    {
        if ($tournament->status !== 'open') {
            return response()->json([
                'error' => 'Bracket can only be generated for open tournaments',
            ], 400);
        }

        if ($tournament->registrations()->where('status', 'registered')->count() < $tournament->min_players) {
            return response()->json([
                'error' => 'Not enough registered players to generate bracket',
            ], 400);
        }

        try {
            $this->bracketGenerator->generate($tournament);

            return response()->json([
                'message' => 'Bracket generated successfully',
                'tournament' => $tournament->fresh()->load(['game', 'matches']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancel(Tournament $tournament): JsonResponse
    {
        if (!in_array($tournament->status, ['draft', 'open', 'registration_closed'])) {
            return response()->json([
                'error' => 'Cannot cancel a tournament that has already started or completed',
            ], 400);
        }

        DB::transaction(function () use ($tournament) {
            // Refund all registered players
            foreach ($tournament->registrations as $registration) {
                $registration->user->increment('balance', $registration->entry_fee_paid);

                Transaction::create([
                    'user_id' => $registration->user->id,
                    'amount' => $registration->entry_fee_paid,
                    'type' => 'refund',
                    'status' => 'completed',
                    'description' => "Refund for cancelled tournament: {$tournament->title}",
                ]);
            }

            $tournament->update(['status' => 'cancelled']);
        });

        return response()->json([
            'message' => 'Tournament cancelled and all entry fees refunded',
            'tournament' => $tournament->fresh(),
        ]);
    }

    public function bracket(Tournament $tournament): JsonResponse
    {
        $tournament->load([
            'matches.player1:id,username,avatar',
            'matches.player2:id,username,avatar',
            'matches.winner:id,username,avatar',
        ]);

        $matches = $tournament->matches->map(function ($match) {
            return [
                'id' => $match->id,
                'round' => $match->round_number,
                'match_number' => $match->match_number,
                'bracket_tier' => $match->bracket_tier ?? 'upper',
                'is_grand_final' => $match->is_grand_final ?? false,
                'status' => $match->status,
                'player1_id' => $match->player1_id,
                'player2_id' => $match->player2_id,
                'winner_id' => $match->winner_id,
                'score_player1' => $match->score_player1,
                'score_player2' => $match->score_player2,
                'player1' => $match->player1 ? ['id' => $match->player1->id, 'username' => $match->player1->username, 'avatar' => $match->player1->avatar] : null,
                'player2' => $match->player2 ? ['id' => $match->player2->id, 'username' => $match->player2->username, 'avatar' => $match->player2->avatar] : null,
                'winner' => $match->winner ? ['id' => $match->winner->id, 'username' => $match->winner->username] : null,
                'scheduled_at' => $match->scheduled_at,
                'started_at' => $match->started_at,
                'completed_at' => $match->completed_at,
                'player1_ready' => $match->player1_ready,
                'player2_ready' => $match->player2_ready,
                'game_room_id' => $match->game_room_id,
            ];
        })->sortBy(['round', 'match_number'])->values();

        return response()->json(['matches' => $matches, 'bracket_type' => $tournament->bracket_type]);
    }

    public function leaderboard(Tournament $tournament): JsonResponse
    {
        $tournament->load(['registrations.user:id,username,avatar']);

        $leaderboard = $tournament->registrations->map(function ($reg) use ($tournament) {
            // Count wins from matches
            $wins = $tournament->matches()
                ->where('winner_id', $reg->user_id)
                ->where('status', 'completed')
                ->count();

            return [
                'id' => $reg->id,
                'user' => ['id' => $reg->user->id, 'username' => $reg->user->username],
                'wins' => $wins,
                'points' => $wins * 10 + ($reg->final_rank === 1 ? 50 : ($reg->final_rank === 2 ? 25 : 0)),
                'final_rank' => $reg->final_rank,
                'prize_won' => $reg->prize_won,
                'is_active' => $reg->status === 'active',
                'is_eliminated' => $reg->status === 'eliminated',
                'status' => $reg->status,
            ];
        })->sortByDesc('points')->values();

        return response()->json([
            'leaderboard' => $leaderboard,
            'tournament' => [
                'id' => $tournament->id,
                'title' => $tournament->title,
                'status' => $tournament->status,
                'total_pool' => $tournament->total_pool,
                'entry_fee' => $tournament->entry_fee,
                'registered_players_count' => $tournament->registered_players_count,
                'winner_prize_percentage' => $tournament->winner_prize_percentage,
                'platform_commission_percentage' => $tournament->platform_commission_percentage,
            ],
        ]);
    }

    public function userRegistrations(Request $request): JsonResponse
    {
        $user = $request->user();

        $registrations = TournamentRegistration::with(['tournament.game', 'tournament.matches'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'registrations' => $registrations->map(function ($reg) {
                return [
                    'id' => $reg->id,
                    'tournament' => [
                        'id' => $reg->tournament->id,
                        'title' => $reg->tournament->title,
                        'game' => [
                            'id' => $reg->tournament->game->id,
                            'title' => $reg->tournament->game->name ?? $reg->tournament->game->title,
                            'image' => $reg->tournament->game->image,
                        ],
                        'entry_fee' => $reg->tournament->entry_fee,
                        'status' => $reg->tournament->status,
                        'starts_at' => $reg->tournament->starts_at,
                    ],
                    'bracket_position' => $reg->bracket_position,
                    'received_bye' => $reg->received_bye,
                    'final_rank' => $reg->final_rank,
                    'prize_won' => $reg->prize_won,
                    'status' => $reg->status,
                    'registered_at' => $reg->registered_at,
                ];
            }),
        ]);
    }
}
