<?php

namespace App\Http\Controllers\Api;

use App\Models\ContentBonusRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentBonusController
{
    /**
     * Submit a new content bonus request
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->is_super_agent) {
            return response()->json(['error' => 'Not a Super Agent'], 403);
        }

        $request->validate([
            'platform' => 'required|in:youtube,instagram,tiktok',
            'video_url' => 'required|url|unique:content_bonus_requests,video_url',
            'view_count' => 'required|integer|min:0',
            'screenshot' => 'nullable|image|max:2048',
        ]);

        // Validate that URL matches platform
        $url = strtolower($request->video_url);
        $platformCheck = [
            'youtube' => strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false,
            'instagram' => strpos($url, 'instagram.com') !== false,
            'tiktok' => strpos($url, 'tiktok.com') !== false,
        ];

        if (!$platformCheck[$request->platform]) {
            return response()->json(['error' => 'URL does not match the selected platform'], 422);
        }

        // Check minimum view threshold
        $platformThresholds = [
            'youtube' => 10000,
            'instagram' => 10000,
            'tiktok' => 10000,
        ];

        if ($request->view_count < ($platformThresholds[$request->platform] ?? 10000)) {
            return response()->json([
                'error' => 'View count below minimum threshold',
                'minimum' => $platformThresholds[$request->platform] ?? 10000,
            ], 422);
        }

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('content-bonuses', 'public');
        }

        $contentRequest = ContentBonusRequest::create([
            'user_id' => $user->id,
            'platform' => $request->platform,
            'video_url' => $request->video_url,
            'view_count' => $request->view_count,
            'screenshot_path' => $screenshotPath,
            'status' => 'pending',
        ]);

        $estimatedBonus = $contentRequest->getEstimatedBonus();

        \Log::info("Content bonus request submitted by SA {$user->id} for {$request->platform} ({$request->view_count} views)");

        return response()->json([
            'success' => true,
            'request_id' => $contentRequest->id,
            'status' => 'pending',
            'estimated_bonus' => (float)$estimatedBonus,
            'message' => 'Video submitted successfully. Awaiting admin review.',
        ], 201);
    }

    /**
     * Get own content bonus requests
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->is_super_agent) {
            return response()->json(['error' => 'Not a Super Agent'], 403);
        }

        $query = $user->superAgentContentRequests();

        // Filter by platform
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()
            ->paginate($request->get('per_page', 15));

        $enriched = $requests->map(function ($req) {
            return [
                'id' => $req->id,
                'platform' => $req->platform,
                'platform_label' => ContentBonusRequest::PLATFORMS[$req->platform] ?? $req->platform,
                'url' => $req->video_url,
                'view_count' => $req->view_count,
                'status' => $req->status,
                'bonus_amount' => $req->bonus_amount ? (float)$req->bonus_amount : null,
                'admin_note' => $req->admin_note,
                'created_at' => $req->created_at->format('Y-m-d H:i:s'),
                'reviewed_at' => $req->reviewed_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'data' => $enriched->values(),
            'pagination' => [
                'total' => $requests->total(),
                'per_page' => $requests->perPage(),
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
            ],
        ]);
    }
}
