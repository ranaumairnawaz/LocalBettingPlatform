<?php

namespace App\Http\Controllers\Api;

use App\Models\SuperAgentCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class StatementController
{
    /**
     * Get or generate monthly PDF statement
     */
    public function show($year, $month)
    {
        $user = auth()->user();

        if (!$user->is_super_agent) {
            return response()->json(['error' => 'Not a Super Agent'], 403);
        }

        // Validate year and month
        if (!checkdate($month, 1, $year)) {
            return response()->json(['error' => 'Invalid year or month'], 422);
        }

        // Calculate statement period
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get commission data for the month
        $commissions = SuperAgentCommission::where('super_agent_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('sourceUser')
            ->get();

        // Calculate summaries
        $depositCommissions = $commissions->where('commission_type', 'deposit_commission');
        $gameCommissions = $commissions->where('commission_type', 'game_commission');

        $depositTotal = $depositCommissions->sum('commission_amount');
        $gameTotal = $gameCommissions->sum('commission_amount');

        // Get content bonus payments for the month
        $contentBonuses = $user->superAgentContentRequests()
            ->where('status', 'approved')
            ->whereBetween('reviewed_at', [$startDate, $endDate])
            ->sum('bonus_amount');

        $grandTotal = $depositTotal + $gameTotal + $contentBonuses;

        // Generate HTML for PDF
        $html = $this->generateStatementHTML(
            $user,
            $year,
            $month,
            $depositTotal,
            $gameTotal,
            $contentBonuses,
            $grandTotal,
            $commissions
        );

        // Generate PDF file name
        $filename = "statement-{$user->id}-{$year}-{$month}.pdf";

        // For now, return JSON with statement data
        return response()->json([
            'year' => $year,
            'month' => $month,
            'period' => $startDate->format('F Y'),
            'team_name' => $user->super_agent_team_name,
            'rank' => $user->superAgentRankLabel(),
            'summary' => [
                'deposit_commissions' => (float)$depositTotal,
                'game_commissions' => (float)$gameTotal,
                'content_bonuses' => (float)$contentBonuses,
                'total' => (float)$grandTotal,
            ],
            'transactions' => $commissions->map(function ($c) {
                return [
                    'type' => $c->commission_type,
                    'level' => $c->referral_level,
                    'source_user' => $c->sourceUser->username,
                    'amount' => (float)$c->commission_amount,
                    'date' => $c->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'download_url' => url("/api/super-agent/statement/{$year}/{$month}/html"),
        ]);
    }

    /**
     * Get statement as printable HTML page
     */
    public function showHtml($year, $month)
    {
        $user = auth()->user();

        if (!$user->is_super_agent) {
            abort(403, 'Not a Super Agent');
        }

        // Validate year and month
        if (!checkdate($month, 1, $year)) {
            abort(422, 'Invalid year or month');
        }

        // Calculate statement period
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get commission data for the month
        $commissions = SuperAgentCommission::where('super_agent_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('sourceUser')
            ->get();

        // Calculate summaries
        $depositCommissions = $commissions->where('commission_type', 'deposit_commission');
        $gameCommissions = $commissions->where('commission_type', 'game_commission');

        $depositTotal = $depositCommissions->sum('commission_amount');
        $gameTotal = $gameCommissions->sum('commission_amount');

        // Get content bonus payments for the month
        $contentBonuses = $user->superAgentContentRequests()
            ->where('status', 'approved')
            ->whereBetween('reviewed_at', [$startDate, $endDate])
            ->sum('bonus_amount');

        $grandTotal = $depositTotal + $gameTotal + $contentBonuses;

        // Generate HTML
        $html = $this->generateStatementHTML(
            $user,
            $year,
            $month,
            $depositTotal,
            $gameTotal,
            $contentBonuses,
            $grandTotal,
            $commissions
        );

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Helper: Generate HTML for PDF statement
     */
    private function generateStatementHTML($user, $year, $month, $depositTotal, $gameTotal, $contentBonuses, $grandTotal, $commissions)
    {
        $periodName = Carbon::createFromDate($year, $month, 1)->format('F Y');
        $now = now();

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Super Agent Statement - {$periodName}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        .stats { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0; }
        .stat-box { border: 1px solid #ddd; padding: 15px; }
        .stat-box h3 { margin: 0 0 10px 0; }
        .stat-box .amount { font-size: 24px; font-weight: bold; color: #2ecc71; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th, table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        table th { background-color: #f5f5f5; font-weight: bold; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .footer { margin-top: 40px; font-size: 12px; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Super Agent Monthly Statement</h1>
        <p>Team: {$user->super_agent_team_name}</p>
        <p>Rank: {$user->superAgentRankLabel()}</p>
        <p>Period: {$periodName}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <h3>Deposit Commissions</h3>
            <div class="amount">Rs. {$depositTotal}</div>
        </div>
        <div class="stat-box">
            <h3>Game Commissions</h3>
            <div class="amount">Rs. {$gameTotal}</div>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box">
            <h3>Content Bonuses</h3>
            <div class="amount">Rs. {$contentBonuses}</div>
        </div>
        <div class="stat-box">
            <h3>Total Earnings</h3>
            <div class="amount">Rs. {$grandTotal}</div>
        </div>
    </div>

    <h2>Transaction Details</h2>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Level</th>
                <th>Source</th>
                <th>Amount</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
HTML;

        foreach ($commissions as $c) {
            $type = $c->commission_type === 'deposit_commission' ? 'Deposit' : 'Game';
            $html .= <<<HTML
            <tr>
                <td>{$type}</td>
                <td>Level {$c->referral_level}</td>
                <td>{$c->sourceUser->username}</td>
                <td>Rs. {$c->commission_amount}</td>
                <td>{$c->created_at->format('Y-m-d')}</td>
            </tr>
HTML;
        }

        $html .= <<<HTML
            <tr class="total-row">
                <td colspan="3">Total</td>
                <td>Rs. {$grandTotal}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>This statement was generated on {$now->format('Y-m-d H:i:s')}</p>
        <p>This is a non-binding, informational statement. For official records, contact support.</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
}
