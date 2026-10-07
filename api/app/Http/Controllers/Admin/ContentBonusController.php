<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContentBonusRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ContentBonusController
{
    /**
     * Display all content bonus requests with filters
     */
    public function index(Request $request): View
    {
        $query = ContentBonusRequest::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by platform
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $contentRequests = $query->with('user', 'reviewedByUser')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'pending' => ContentBonusRequest::where('status', 'pending')->count(),
            'approved' => ContentBonusRequest::where('status', 'approved')->count(),
            'rejected' => ContentBonusRequest::where('status', 'rejected')->count(),
            'total_paid' => ContentBonusRequest::where('status', 'approved')->sum('bonus_amount'),
        ];

        return view('admin.super-agents.content-requests', [
            'contentRequests' => $contentRequests,
            'stats' => $stats,
            'platforms' => ContentBonusRequest::PLATFORMS,
        ]);
    }

    /**
     * Show single content request details
     */
    public function show($id): View
    {
        $request = ContentBonusRequest::with('user', 'reviewedByUser')->findOrFail($id);

        return view('admin.super-agents.content-request-show', [
            'request' => $request,
            'estimatedBonus' => $request->getEstimatedBonus(),
        ]);
    }

    /**
     * Approve a content bonus request
     */
    public function approve(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'bonus_amount' => 'required|numeric|min:0.01|max:10000',
        ]);

        $contentRequest = ContentBonusRequest::findOrFail($id);

        if ($contentRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Request is not pending');
        }

        $user = $contentRequest->user;
        $bonusAmount = $request->bonus_amount;

        // Create transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $bonusAmount,
            'type' => 'bonus',
            'method' => 'Admin Adjustment',
            'status' => 'completed',
            'reference' => 'BONUS_' . $contentRequest->id . '_' . time() . '_' . rand(1000, 9999),
            'admin_note' => "Content bonus approved for {$contentRequest->platform} video ({$contentRequest->view_count} views)",
        ]);

        // Update user balance
        $user->increment('balance', $bonusAmount);

        // Update content request
        $contentRequest->update([
            'status' => 'approved',
            'bonus_amount' => $bonusAmount,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        \Log::info("Content bonus approved for user {$user->id}: Rs. {$bonusAmount}");

        return redirect()->back()->with('success', "Bonus approved! Rs. {$bonusAmount} credited to user.");
    }

    /**
     * Reject a content bonus request
     */
    public function reject(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'rejection_note' => 'required|string|max:500',
        ]);

        $contentRequest = ContentBonusRequest::findOrFail($id);

        if ($contentRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Request is not pending');
        }

        $contentRequest->update([
            'status' => 'rejected',
            'admin_note' => $request->rejection_note,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        \Log::info("Content bonus rejected for user {$contentRequest->user_id}");

        return redirect()->back()->with('success', 'Request rejected successfully');
    }
}
