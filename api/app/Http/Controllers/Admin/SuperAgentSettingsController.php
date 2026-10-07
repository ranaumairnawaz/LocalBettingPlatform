<?php

namespace App\Http\Controllers\Admin;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SuperAgentSettingsController
{
    /**
     * Display Super Agent settings form
     */
    public function index(): View
    {
        // Fetch all settings
        $settings = [
            // Tiered commission rates
            'bronze' => [
                'l1' => SystemSetting::get('super_agent_commission_bronze_l1', 5),
                'l2' => SystemSetting::get('super_agent_commission_bronze_l2', 3),
                'l3' => SystemSetting::get('super_agent_commission_bronze_l3', 2),
                'l4' => SystemSetting::get('super_agent_commission_bronze_l4', 1),
                'l5' => SystemSetting::get('super_agent_commission_bronze_l5', 0.5),
            ],
            'silver' => [
                'l1' => SystemSetting::get('super_agent_commission_silver_l1', 6),
                'l2' => SystemSetting::get('super_agent_commission_silver_l2', 4),
                'l3' => SystemSetting::get('super_agent_commission_silver_l3', 2.5),
                'l4' => SystemSetting::get('super_agent_commission_silver_l4', 1.5),
                'l5' => SystemSetting::get('super_agent_commission_silver_l5', 0.7),
            ],
            'gold' => [
                'l1' => SystemSetting::get('super_agent_commission_gold_l1', 7),
                'l2' => SystemSetting::get('super_agent_commission_gold_l2', 5),
                'l3' => SystemSetting::get('super_agent_commission_gold_l3', 3),
                'l4' => SystemSetting::get('super_agent_commission_gold_l4', 2),
                'l5' => SystemSetting::get('super_agent_commission_gold_l5', 1),
            ],
            'diamond' => [
                'l1' => SystemSetting::get('super_agent_commission_diamond_l1', 8),
                'l2' => SystemSetting::get('super_agent_commission_diamond_l2', 6),
                'l3' => SystemSetting::get('super_agent_commission_diamond_l3', 4),
                'l4' => SystemSetting::get('super_agent_commission_diamond_l4', 2),
                'l5' => SystemSetting::get('super_agent_commission_diamond_l5', 1.2),
            ],
            // Game commission pass-through
            'game_commission_rate' => SystemSetting::get('super_agent_game_commission_rate', 10),
            // Content bonus rates
            'content_bonus_youtube_10k' => SystemSetting::get('content_bonus_youtube_10k', 100),
            'content_bonus_youtube_50k' => SystemSetting::get('content_bonus_youtube_50k', 200),
            'content_bonus_youtube_100k' => SystemSetting::get('content_bonus_youtube_100k', 300),
            'content_bonus_youtube_500k' => SystemSetting::get('content_bonus_youtube_500k', 500),
            'content_bonus_instagram_10k' => SystemSetting::get('content_bonus_instagram_10k', 80),
            'content_bonus_instagram_50k' => SystemSetting::get('content_bonus_instagram_50k', 160),
            'content_bonus_instagram_100k' => SystemSetting::get('content_bonus_instagram_100k', 240),
            'content_bonus_instagram_500k' => SystemSetting::get('content_bonus_instagram_500k', 400),
            'content_bonus_tiktok_10k' => SystemSetting::get('content_bonus_tiktok_10k', 90),
            'content_bonus_tiktok_50k' => SystemSetting::get('content_bonus_tiktok_50k', 180),
            'content_bonus_tiktok_100k' => SystemSetting::get('content_bonus_tiktok_100k', 270),
            'content_bonus_tiktok_500k' => SystemSetting::get('content_bonus_tiktok_500k', 450),
            // Rank thresholds
            'rank_upgrade_by' => SystemSetting::get('super_agent_rank_upgrade_by', 'team_size'),
            'bronze_to_silver_threshold' => SystemSetting::get('super_agent_bronze_to_silver_threshold', 10),
            'silver_to_gold_threshold' => SystemSetting::get('super_agent_silver_to_gold_threshold', 50),
            'gold_to_diamond_threshold' => SystemSetting::get('super_agent_gold_to_diamond_threshold', 100),
        ];

        return view('admin.super-agents.settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update Super Agent settings
     */
    public function update(Request $request): RedirectResponse
    {
        // Build commission validation rules matching blade name="super_agent_commission_{rank}_l{level}"
        $commissionRules = [];
        foreach (['bronze', 'silver', 'gold', 'diamond', 'platinum'] as $rank) {
            foreach ([1, 2, 3, 4, 5] as $level) {
                $commissionRules["super_agent_commission_{$rank}_l{$level}"] = 'required|numeric|min:0|max:50';
            }
        }

        // Build content bonus rules matching blade name="content_bonus_{platform}_{raw_views}"
        $contentBonusRules = [];
        foreach (['youtube', 'instagram', 'tiktok'] as $platform) {
            foreach ([10000, 50000, 100000, 500000] as $views) {
                $contentBonusRules["content_bonus_{$platform}_{$views}"] = 'required|numeric|min:0';
            }
        }

        // Build rank threshold rules matching blade name="rank_upgrade_team_size_{rank}" etc.
        $rankRules = [];
        foreach (['bronze', 'silver', 'gold', 'diamond', 'platinum'] as $rank) {
            $rankRules["rank_upgrade_team_size_{$rank}"] = 'required|integer|min:1';
            $rankRules["rank_upgrade_earnings_{$rank}"]  = 'required|integer|min:1';
        }

        $allRules = array_merge($commissionRules, $contentBonusRules, $rankRules, [
            'super_agent_game_commission_rate' => 'required|numeric|min:0|max:100',
            'rank_upgrade_mode'               => 'required|in:team_size,earnings,manual',
        ]);

        $validated = $request->validate($allRules);

        // Save commission rates
        foreach (['bronze', 'silver', 'gold', 'diamond', 'platinum'] as $rank) {
            foreach ([1, 2, 3, 4, 5] as $level) {
                $field = "super_agent_commission_{$rank}_l{$level}";
                SystemSetting::updateOrCreate(
                    ['key' => $field],
                    ['value' => $validated[$field]]
                );
            }
        }

        // Save game commission rate
        SystemSetting::updateOrCreate(
            ['key' => 'super_agent_game_commission_rate'],
            ['value' => $validated['super_agent_game_commission_rate']]
        );

        // Save content bonus rates — also store under the _Xk alias used by index()
        $milestoneMap = [10000 => '10k', 50000 => '50k', 100000 => '100k', 500000 => '500k'];
        foreach (['youtube', 'instagram', 'tiktok'] as $platform) {
            foreach ([10000, 50000, 100000, 500000] as $views) {
                $field   = "content_bonus_{$platform}_{$views}";
                $aliasKey = "content_bonus_{$platform}_{$milestoneMap[$views]}";
                $value   = $validated[$field];
                // Store under both the raw key and the shorthand alias
                SystemSetting::updateOrCreate(['key' => $field],    ['value' => $value]);
                SystemSetting::updateOrCreate(['key' => $aliasKey], ['value' => $value]);
            }
        }

        // Save rank thresholds
        foreach (['bronze', 'silver', 'gold', 'diamond', 'platinum'] as $rank) {
            SystemSetting::updateOrCreate(
                ['key' => "rank_upgrade_team_size_{$rank}"],
                ['value' => $validated["rank_upgrade_team_size_{$rank}"]]
            );
            SystemSetting::updateOrCreate(
                ['key' => "rank_upgrade_earnings_{$rank}"],
                ['value' => $validated["rank_upgrade_earnings_{$rank}"]]
            );
        }

        // Save upgrade mode
        SystemSetting::updateOrCreate(
            ['key' => 'super_agent_rank_upgrade_by'],
            ['value' => $validated['rank_upgrade_mode']]
        );

        \Log::info('Super Agent settings updated by admin');

        return redirect()->back()->with('success', 'Settings updated successfully');
    }
}
