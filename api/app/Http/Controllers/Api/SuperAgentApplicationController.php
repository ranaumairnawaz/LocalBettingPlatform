<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAgentApplicationController
{
    /**
     * Submit an application to become a Super Agent
     */
    public function apply(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Check if already a Super Agent
        if ($user->is_super_agent) {
            return response()->json(['error' => 'You are already a Super Agent'], 422);
        }

        // Check if already applied
        if ($user->hasPendingApplication()) {
            return response()->json(['error' => 'You have a pending application'], 422);
        }

        $request->validate([
            'group_name' => 'required|string|min:3|max:150|unique:users,super_agent_team_name',
            'platform' => 'required|in:whatsapp,telegram,facebook,instagram,tiktok,discord,other',
            'member_count' => 'required|integer|min:5',
            'group_proof' => 'required|image|max:5120|mimes:jpeg,png,jpg,gif',
        ]);

        // Store proof screenshot
        $proofPath = $request->file('group_proof')->store('super-agent-proofs', 'public');

        $user->update([
            'super_agent_team_name' => $request->group_name,
            'super_agent_application_status' => 'pending',
            'super_agent_group_proof' => $proofPath,
        ]);

        \Log::info("Super Agent application submitted by user {$user->id} for group: {$request->group_name}");

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully. You will be notified once it is reviewed.',
            'status' => 'pending',
        ], 201);
    }

    /**
     * Check application status
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();

        if ($user->is_super_agent) {
            return response()->json([
                'status' => 'approved',
                'message' => 'You are already a Super Agent',
                'team_name' => $user->super_agent_team_name,
                'rank' => $user->super_agent_rank,
                'referral_code' => $user->super_agent_referral_code,
                'approved_at' => $user->super_agent_approved_at->format('Y-m-d H:i:s'),
            ]);
        }

        if ($user->super_agent_application_status === 'none' || !$user->super_agent_application_status) {
            return response()->json([
                'status' => 'none',
                'message' => 'No application submitted',
                'can_apply' => $user->canApplyToBecomeSuperAgent(),
            ]);
        }

        return response()->json([
            'status' => $user->super_agent_application_status,
            'message' => match($user->super_agent_application_status) {
                'pending' => 'Your application is under review',
                'rejected' => 'Your application was rejected',
                default => $user->super_agent_application_status,
            },
            'team_name' => $user->super_agent_team_name,
            'rejection_note' => $user->super_agent_application_note,
            'rejected_at' => $user->super_agent_rejected_at?->format('Y-m-d H:i:s'),
            'submitted_at' => $user->created_at->format('Y-m-d H:i:s'),
        ]);
    }
}
