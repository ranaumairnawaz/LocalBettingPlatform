<?php

namespace App\Http\Controllers\Admin;

use App\Models\SuperAgentChallenge;
use App\Models\SuperAgentChallengeEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SuperAgentChallengeController
{
    /**
     * Display all challenges
     */
    public function index(): View
    {
        // Fetch all challenges — split into groups for the view (no paginator needed here)
        $all = SuperAgentChallenge::with('createdBy')->orderBy('starts_at', 'desc')->get();

        $now = now();
        $active    = $all->filter(fn($c) => $c->is_active && $c->starts_at <= $now && $c->ends_at >= $now)->values();
        $upcoming  = $all->filter(fn($c) => $c->is_active && $c->starts_at > $now)->values();
        $completed = $all->filter(fn($c) => $c->ends_at < $now)->values();

        $stats = [
            'active'   => $active->count(),
            'upcoming' => $upcoming->count(),
            'ended'    => $completed->count(),
        ];

        // Also pass paginated list for the "all challenges" table at the bottom
        $challenges = SuperAgentChallenge::orderBy('starts_at', 'desc')->paginate(20);

        return view('admin.super-agents.challenges', [
            'active'     => $active,
            'upcoming'   => $upcoming,
            'completed'  => $completed,
            'challenges' => $challenges,
            'stats'      => $stats,
            'types'      => SuperAgentChallenge::TYPES,
        ]);
    }

    /**
     * Show create challenge form
     */
    public function create(): View
    {
        return view('admin.super-agents.challenge-form', [
            'types' => SuperAgentChallenge::TYPES,
            'challenge' => null,
        ]);
    }

    /**
     * Store a new challenge
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'challenge_type' => 'required|in:team_signups,team_deposits,team_games_played',
            'target_value' => 'required|integer|min:1',
            'reward_amount' => 'required|numeric|min:0.01|max:100000',
            'starts_at' => 'required|date|after_or_equal:now',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        $challenge = SuperAgentChallenge::create([
            'title' => $request->title,
            'description' => $request->description,
            'challenge_type' => $request->challenge_type,
            'target_value' => $request->target_value,
            'reward_amount' => $request->reward_amount,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        \Log::info("New challenge created: {$challenge->title} (ID: {$challenge->id})");

        return redirect()->route('admin.super_agents.challenges.index')
            ->with('success', 'Challenge created successfully');
    }

    /**
     * Show edit challenge form
     */
    public function edit($id): View
    {
        $challenge = SuperAgentChallenge::findOrFail($id);

        return view('admin.super-agents.challenge-form', [
            'challenge' => $challenge,
            'types' => SuperAgentChallenge::TYPES,
        ]);
    }

    /**
     * Update a challenge
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $challenge = SuperAgentChallenge::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'challenge_type' => 'required|in:team_signups,team_deposits,team_games_played',
            'target_value' => 'required|integer|min:1',
            'reward_amount' => 'required|numeric|min:0.01|max:100000',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        $challenge->update([
            'title' => $request->title,
            'description' => $request->description,
            'challenge_type' => $request->challenge_type,
            'target_value' => $request->target_value,
            'reward_amount' => $request->reward_amount,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
        ]);

        \Log::info("Challenge updated: {$challenge->title} (ID: {$challenge->id})");

        return redirect()->route('admin.super_agents.challenges.index')
            ->with('success', 'Challenge updated successfully');
    }

    /**
     * Toggle challenge active status
     */
    public function toggle($id): RedirectResponse
    {
        $challenge = SuperAgentChallenge::findOrFail($id);
        $challenge->update(['is_active' => !$challenge->is_active]);

        $status = $challenge->is_active ? 'activated' : 'deactivated';
        \Log::info("Challenge {$status}: {$challenge->title} (ID: {$challenge->id})");

        return redirect()->back()->with('success', "Challenge {$status} successfully");
    }

    /**
     * Delete a challenge
     */
    public function destroy($id): RedirectResponse
    {
        $challenge = SuperAgentChallenge::findOrFail($id);

        // Check if challenge has entries
        if ($challenge->entries()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete a challenge with existing entries');
        }

        $challengeTitle = $challenge->title;
        $challenge->delete();

        \Log::info("Challenge deleted: {$challengeTitle}");

        return redirect()->route('admin.super_agents.challenges.index')
            ->with('success', 'Challenge deleted successfully');
    }
}
