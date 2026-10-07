<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SuperAgentApplicationController
{
    /**
     * Display list of Super Agent applications with search & status filter
     */
    public function index(Request $request): View
    {
        $query = User::whereIn('super_agent_application_status', ['pending', 'approved', 'rejected']);

        // Status filter — default to pending if not specified
        if ($request->filled('status')) {
            $query->where('super_agent_application_status', $request->status);
        } else {
            $query->where('super_agent_application_status', 'pending');
        }

        // Username search
        if ($request->filled('search')) {
            $query->where('username', 'like', '%' . $request->search . '%');
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.super-agents.applications', [
            'applications' => $applications,
            'pendingCount' => User::where('super_agent_application_status', 'pending')->count(),
        ]);
    }

    /**
     * Show single application details
     */
    public function show($id): View
    {
        $application = User::findOrFail($id);

        if ($application->super_agent_application_status !== 'pending') {
            abort(404);
        }

        return view('admin.super-agents.application-show', [
            'application' => $application,
        ]);
    }

    /**
     * Approve a Super Agent application
     */
    public function approve(Request $request, $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->super_agent_application_status !== 'pending') {
            return redirect()->back()->with('error', 'Application is not pending');
        }

        if ($user->is_super_agent) {
            return redirect()->back()->with('error', 'User is already a Super Agent');
        }

        // Generate unique referral code — cap slug at 20 chars to keep codes short
        $teamSlug = substr(Str::slug($user->super_agent_team_name), 0, 20);
        $random = Str::upper(Str::random(6));
        $referralCode = "SA-{$teamSlug}-{$random}";

        // Ensure uniqueness
        while (User::where('super_agent_referral_code', $referralCode)->exists()) {
            $random = Str::upper(Str::random(6));
            $referralCode = "SA-{$teamSlug}-{$random}";
        }

        $user->update([
            'is_super_agent' => true,
            'super_agent_rank' => 'bronze',
            'super_agent_referral_code' => $referralCode,
            'super_agent_approved_at' => now(),
            'super_agent_application_status' => 'approved',
            'super_agent_application_note' => null,
        ]);

        // TODO: Send email notification to user
        // Mail::send(new SuperAgentApprovedMail($user));

        \Log::info("Super Agent application approved for user {$user->id} by admin");

        return redirect()->back()->with('success', "Super Agent application approved! Referral code: {$referralCode}");
    }

    /**
     * Reject a Super Agent application
     */
    public function reject(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'rejection_note' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($id);

        if ($user->super_agent_application_status !== 'pending') {
            return redirect()->back()->with('error', 'Application is not pending');
        }

        $user->update([
            'super_agent_application_status' => 'rejected',
            'super_agent_application_note' => $request->rejection_note,
            'super_agent_rejected_at' => now(),
        ]);

        // TODO: Send rejection email to user
        // Mail::send(new SuperAgentRejectedMail($user, $request->rejection_note));

        \Log::info("Super Agent application rejected for user {$user->id}");

        return redirect()->back()->with('success', 'Application rejected successfully');
    }
}
