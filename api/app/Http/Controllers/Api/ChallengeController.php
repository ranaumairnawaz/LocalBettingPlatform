<?php

namespace App\Http\Controllers\Api;

use App\Models\SuperAgentChallenge;
use Illuminate\Http\JsonResponse;

class ChallengeController
{
    /**
     * Get active, upcoming, and completed challenges for a Super Agent
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->is_super_agent) {
            return response()->json(['error' => 'Not a Super Agent'], 403);
        }

        // Get all challenges with user's entry data
        $challenges = SuperAgentChallenge::where('is_active', true)
            ->with(['entries' => function ($q) use ($user) {
                $q->where('super_agent_id', $user->id);
            }])
            ->get();

        $active = [];
        $upcoming = [];
        $completed = [];

        foreach ($challenges as $challenge) {
            $entry = $challenge->entries->first();
            $progress = $entry?->progress ?? 0;
            $isCompleted = $entry && $entry->completed_at;

            $challengeData = [
                'id' => $challenge->id,
                'title' => $challenge->title,
                'description' => $challenge->description,
                'type' => $challenge->challenge_type,
                'type_label' => $challenge->getTypeLabel(),
                'target_value' => $challenge->target_value,
                'reward_amount' => (float)$challenge->reward_amount,
                'my_progress' => $progress,
                'progress_percent' => $challenge->target_value > 0 ? 
                    min(100, (int)(($progress / $challenge->target_value) * 100)) : 0,
                'is_completed' => $isCompleted,
                'completed_at' => $entry?->completed_at?->format('Y-m-d H:i:s'),
                'starts_at' => $challenge->starts_at->format('Y-m-d H:i:s'),
                'ends_at' => $challenge->ends_at->format('Y-m-d H:i:s'),
            ];

            if ($challenge->isRunning()) {
                $challengeData['time_remaining'] = $challenge->ends_at->diffForHumans(now(), true);
                $active[] = $challengeData;
            } elseif ($challenge->isUpcoming()) {
                $challengeData['time_until_start'] = $challenge->starts_at->diffForHumans(now(), true);
                $upcoming[] = $challengeData;
            } else {
                $completed[] = $challengeData;
            }
        }

        return response()->json([
            'active' => $active,
            'upcoming' => $upcoming,
            'completed' => $completed,
        ]);
    }
}
