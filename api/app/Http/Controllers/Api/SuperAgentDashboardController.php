<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\SuperAgentCommission;
use App\Models\SuperAgentChallenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuperAgentDashboardController
{
    /**
     * Get Super Agent dashboard summary
     */
    public function dashboard(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->is_super_agent) {
            return response()->json(['error' => 'Not a Super Agent'], 403);
        }

        $teamSize = $user->superAgentTeamSize();
        $totalEarnings = $user->superAgentTotalEarnings();
        $thisMonthEarnings = $user->superAgentThisMonthEarnings();

        // Calculate rank progress
        $currentRank = $user->super_agent_rank;
        $rankThreshold = $this->getRankThreshold($currentRank);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'team_name' => $user->super_agent_team_name,
                'rank' => $user->super_agent_rank,
                'rank_label' => $user->superAgentRankLabel(),
            ],
            'stats' => [
                'team_size' => $teamSize,
                'total_earnings' => (float)$totalEarnings,
                'this_month_earnings' => (float)$thisMonthEarnings,
                'rank_progress' => [
                    'current_threshold' => $rankThreshold['current'],
                    'next_threshold' => $rankThreshold['next'],
                    'progress_percent' => $rankThreshold['current'] > 0 ? 
                        min(100, (int)(($teamSize / $rankThreshold['current']) * 100)) : 0,
                ],
            ],
            'rank_badge' => $this->getRankBadge($currentRank),
            'referral_link' => $user->super_agent_referral_code,
        ]);
    }

    /**
     * Get paginated earnings/commissions
     */
    public function earnings(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->is_super_agent) {
            return response()->json(['error' => 'Not a Super Agent'], 403);
        }

        $query = $user->superAgentCommissions();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('commission_type', $request->type);
        }

        // Filter by level
        if ($request->filled('level')) {
            $query->where('referral_level', $request->level);
        }

        $commissions = $query->with('sourceUser')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $commissions->items(),
            'pagination' => [
                'total' => $commissions->total(),
                'per_page' => $commissions->perPage(),
                'current_page' => $commissions->currentPage(),
                'last_page' => $commissions->lastPage(),
            ],
        ]);
    }

    /**
     * Get team members (level 1 recruits)
     */
    public function team(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->is_super_agent) {
            return response()->json(['error' => 'Not a Super Agent'], 403);
        }

        $query = User::where('referred_by', $user->id);

        // Filter by activity
        if ($request->filled('activity')) {
            $activity = $request->activity;
            if ($activity === 'active') {
                $query->whereHas('transactions', function ($q) {
                    $q->where('created_at', '>=', now()->subDays(30));
                });
            } elseif ($activity === 'dormant') {
                $query->whereDoesntHave('transactions', function ($q) {
                    $q->where('created_at', '>=', now()->subDays(30));
                });
            } elseif ($activity === 'new') {
                $query->where('created_at', '>=', now()->subDays(7));
            }
        }

        $members = $query->select('id', 'username', 'avatar', 'created_at', 'balance')
            ->latest()
            ->paginate($request->get('per_page', 15));

        // Enrich with activity data
        $enriched = $members->map(function ($member) {
            $totalDeposits = $member->transactions()
                ->where('type', 'deposit')
                ->where('status', 'completed')
                ->sum('amount');

            $lastActivityAt = $member->transactions()
                ->latest()
                ->first()?->created_at;

            $daysInactive = $lastActivityAt ? $lastActivityAt->diffInDays(now()) : $member->created_at->diffInDays(now());

            $activityBadge = 'new';
            if ($daysInactive > 30) {
                $activityBadge = 'dormant';
            } elseif ($daysInactive <= 7) {
                $activityBadge = 'active';
            }

            return [
                'id' => $member->id,
                'name' => $member->username,
                'avatar' => $member->avatar,
                'join_date' => $member->created_at->format('Y-m-d'),
                'total_deposits' => (float)$totalDeposits,
                'activity_badge' => $activityBadge,
                'bet_volume' => (float)($member->gameLogs()->sum('bet_amount') ?? 0),
            ];
        });

        return response()->json([
            'data' => $enriched->values(),
            'pagination' => [
                'total' => $members->total(),
                'per_page' => $members->perPage(),
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
            ],
        ]);
    }

    /**
     * Get Super Agent leaderboard
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $sortBy = $request->get('sort_by', 'earnings'); // earnings or team_size

        $query = User::where('is_super_agent', true);

        $agents = $query->get()->map(function ($agent) use ($sortBy) {
            $teamSize = $agent->superAgentTeamSize();
            $earnings = $agent->superAgentTotalEarnings();

            return [
                'id' => $agent->id,
                'team_name' => $agent->super_agent_team_name,
                'earnings' => (float)$earnings,
                'team_size' => $teamSize,
                'rank_badge' => $this->getRankBadge($agent->super_agent_rank),
                'rank' => $agent->super_agent_rank,
            ];
        });

        // Sort based on parameter
        if ($sortBy === 'team_size') {
            $agents = $agents->sortByDesc('team_size');
        } else {
            $agents = $agents->sortByDesc('earnings');
        }

        // Add rank numbers
        $ranked = $agents->values()->map(function ($agent, $index) {
            $agent['rank_number'] = $index + 1;
            $agent['is_self'] = auth()->id() === $agents[$index]['id'];
            return $agent;
        })->slice(0, 50); // Top 50

        return response()->json([
            'data' => $ranked->values(),
        ]);
    }

    /**
     * Update team name
     */
    public function updateTeamName(Request $request): JsonResponse
    {
        $request->validate([
            'team_name' => 'required|string|min:3|max:150|unique:users,super_agent_team_name,' . auth()->id(),
        ]);

        $user = auth()->user();

        if (!$user->is_super_agent) {
            return response()->json(['error' => 'Not a Super Agent'], 403);
        }

        $oldName = $user->super_agent_team_name;
        $user->update(['super_agent_team_name' => $request->team_name]);

        \Log::info("Team name updated for SA {$user->id}: {$oldName} → {$request->team_name}");

        return response()->json([
            'success' => true,
            'team_name' => $request->team_name,
            'referral_code' => $user->super_agent_referral_code,
            'message' => 'Team name updated successfully',
        ]);
    }

    /**
     * Regenerate referral code (respects 30-day cooldown)
     */
    public function regenerateLink(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->is_super_agent) {
            return response()->json(['error' => 'Not a Super Agent'], 403);
        }

        // Check 30-day cooldown
        if ($user->super_agent_referral_code_updated_at && 
            $user->super_agent_referral_code_updated_at->addDays(30)->isFuture()) {
            $nextAvailable = $user->super_agent_referral_code_updated_at->addDays(30);
            return response()->json([
                'error' => 'Code regeneration is on cooldown',
                'next_available' => $nextAvailable->format('Y-m-d'),
                'days_remaining' => $user->super_agent_referral_code_updated_at->addDays(30)->diffInDays(now()),
            ], 429);
        }

        // Generate new code
        $teamName = Str::slug($user->super_agent_team_name);
        $random = Str::random(6);
        $newCode = "SA-{$teamName}-{$random}";

        while (User::where('super_agent_referral_code', $newCode)->where('id', '!=', $user->id)->exists()) {
            $random = Str::random(6);
            $newCode = "SA-{$teamName}-{$random}";
        }

        $user->update([
            'super_agent_referral_code' => $newCode,
            'super_agent_referral_code_updated_at' => now(),
        ]);

        \Log::info("Referral code regenerated for SA {$user->id}. New code: {$newCode}");

        return response()->json([
            'success' => true,
            'new_code' => $newCode,
            'next_available' => now()->addDays(30)->format('Y-m-d'),
            'message' => 'Referral code regenerated successfully',
        ]);
    }

    /**
     * Helper: Get rank badge data
     */
    private function getRankBadge($rank)
    {
        $badges = [
            'bronze' => ['label' => 'Bronze', 'color' => '#CD7F32'],
            'silver' => ['label' => 'Silver', 'color' => '#C0C0C0'],
            'gold' => ['label' => 'Gold', 'color' => '#FFD700'],
            'diamond' => ['label' => 'Diamond', 'color' => '#B9F2FF'],
        ];

        return $badges[$rank] ?? ['label' => 'Unranked', 'color' => '#999'];
    }

    /**
     * Helper: Get rank thresholds
     */
    private function getRankThreshold($rank)
    {
        $thresholds = [
            'bronze' => [
                'current' => \App\Models\SystemSetting::get('super_agent_bronze_to_silver_threshold', 10),
                'next' => \App\Models\SystemSetting::get('super_agent_silver_to_gold_threshold', 50),
            ],
            'silver' => [
                'current' => \App\Models\SystemSetting::get('super_agent_silver_to_gold_threshold', 50),
                'next' => \App\Models\SystemSetting::get('super_agent_gold_to_diamond_threshold', 100),
            ],
            'gold' => [
                'current' => \App\Models\SystemSetting::get('super_agent_gold_to_diamond_threshold', 100),
                'next' => 1000,
            ],
            'diamond' => [
                'current' => 1000,
                'next' => 5000,
            ],
        ];

        return $thresholds[$rank] ?? ['current' => 0, 'next' => 10];
    }
}
