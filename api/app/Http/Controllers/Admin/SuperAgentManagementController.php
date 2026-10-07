<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class SuperAgentManagementController
{
    /**
     * Display all Super Agents with filters
     */
    public function index(Request $request): View
    {
        $query = User::where('is_super_agent', true);

        // Filter by rank
        if ($request->filled('rank')) {
            $query->where('super_agent_rank', $request->rank);
        }

        // Filter by team name
        if ($request->filled('search')) {
            $query->where('super_agent_team_name', 'like', '%' . $request->search . '%')
                  ->orWhere('username', 'like', '%' . $request->search . '%');
        }

        $superAgents = $query->orderBy('super_agent_rank', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_count' => User::where('is_super_agent', true)->count(),
            'active_this_month' => User::where('is_super_agent', true)
                ->whereHas('superAgentCommissions', function ($q) {
                    $q->whereMonth('created_at', now()->month);
                })
                ->count(),
            'pending_applications' => User::where('super_agent_application_status', 'pending')->count(),
        ];

        return view('admin.super-agents.index', [
            'superAgents' => $superAgents,
            'stats' => $stats,
            'ranks' => ['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold', 'diamond' => 'Diamond'],
        ]);
    }

    /**
     * Show details for a specific Super Agent
     */
    public function show($id): View
    {
        $superAgent = User::findOrFail($id);

        if (!$superAgent->is_super_agent) {
            abort(404);
        }

        $teamSize = $superAgent->superAgentTeamSize();
        $totalEarnings = $superAgent->superAgentTotalEarnings();
        $thisMonthEarnings = $superAgent->superAgentThisMonthEarnings();

        $commissions = $superAgent->superAgentCommissions()
            ->latest()
            ->paginate(10);

        $contentRequests = $superAgent->superAgentContentRequests()
            ->latest()
            ->paginate(10);

        $challenges = $superAgent->superAgentChallengeEntries()
            ->with('challenge')
            ->latest()
            ->paginate(10);

        // Get level-1 recruits (direct referrals)
        $recruits = User::where('referred_by', $superAgent->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.super-agents.show', [
            'superAgent' => $superAgent,
            'stats' => [
                'team_size' => $teamSize,
                'total_earnings' => $totalEarnings,
                'this_month_earnings' => $thisMonthEarnings,
                'content_bonuses_paid' => $superAgent->superAgentContentRequests()
                    ->where('status', 'approved')
                    ->sum('bonus_amount'),
            ],
            'commissions' => $commissions,
            'contentRequests' => $contentRequests,
            'challenges' => $challenges,
            'recruits' => $recruits,
        ]);
    }

    /**
     * Promote a regular user to Super Agent
     */
    public function promote(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'team_name' => 'required|string|min:3|max:150|unique:users,super_agent_team_name',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->is_super_agent) {
            return redirect()->back()->with('error', 'User is already a Super Agent');
        }

        // Generate unique referral code
        $teamName = Str::slug($request->team_name);
        $random = Str::random(6);
        $referralCode = "SA-{$teamName}-{$random}";

        while (User::where('super_agent_referral_code', $referralCode)->exists()) {
            $random = Str::random(6);
            $referralCode = "SA-{$teamName}-{$random}";
        }

        $user->update([
            'is_super_agent' => true,
            'super_agent_team_name' => $request->team_name,
            'super_agent_referral_code' => $referralCode,
            'super_agent_rank' => 'bronze',
            'super_agent_approved_at' => now(),
            'super_agent_application_status' => 'approved',
        ]);

        \Log::info("User {$user->id} promoted to Super Agent by admin with team name: {$request->team_name}");

        return redirect()->back()->with('success', "User promoted to Super Agent! Team: {$request->team_name}, Code: {$referralCode}");
    }

    /**
     * Demote a Super Agent to regular user
     */
    public function demote($id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (!$user->is_super_agent) {
            return redirect()->back()->with('error', 'User is not a Super Agent');
        }

        $teamName = $user->super_agent_team_name;

        $user->update([
            'is_super_agent' => false,
            'super_agent_team_name' => null,
            'super_agent_referral_code' => null,
            'super_agent_rank' => null,
            'super_agent_approved_at' => null,
            'super_agent_application_status' => 'none',
        ]);

        \Log::warning("Super Agent {$user->id} (team: {$teamName}) demoted by admin. Existing commissions retained in audit log.");

        return redirect()->back()->with('success', 'Super Agent demoted successfully');
    }

    /**
     * Update Super Agent team name
     */
    public function updateTeamName(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'team_name' => 'required|string|min:3|max:150|unique:users,super_agent_team_name,' . $id,
        ]);

        $user = User::findOrFail($id);

        if (!$user->is_super_agent) {
            return redirect()->back()->with('error', 'User is not a Super Agent');
        }

        $oldName = $user->super_agent_team_name;
        $user->update(['super_agent_team_name' => $request->team_name]);

        \Log::info("Super Agent {$user->id} team name changed from '{$oldName}' to '{$request->team_name}'");

        return redirect()->back()->with('success', 'Team name updated successfully');
    }

    /**
     * Manually trigger referral code regeneration (respects 30-day cooldown)
     */
    public function regenerateCode($id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (!$user->is_super_agent) {
            return redirect()->back()->with('error', 'User is not a Super Agent');
        }

        // Check 30-day cooldown
        if ($user->super_agent_referral_code_updated_at && 
            $user->super_agent_referral_code_updated_at->addDays(30)->isFuture()) {
            $nextAvailable = $user->super_agent_referral_code_updated_at->addDays(30);
            return redirect()->back()->with('error', "Can regenerate code again after {$nextAvailable->format('M d, Y')}");
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

        \Log::info("Referral code regenerated for Super Agent {$user->id}. New code: {$newCode}");

        return redirect()->back()->with('success', "New referral code generated: {$newCode}");
    }
}
