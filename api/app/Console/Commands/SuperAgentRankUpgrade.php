<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Console\Command;

class SuperAgentRankUpgrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'super-agent:upgrade-ranks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically upgrade Super Agent ranks based on team size or earnings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mode = SystemSetting::get('super_agent_rank_upgrade_by', 'team_size');

        $this->info("Running Super Agent rank upgrade check (mode: {$mode})...");

        if ($mode === 'manual') {
            $this->info('Rank upgrade mode is set to manual. Skipping automatic upgrades.');
            return Command::SUCCESS;
        }

        if ($mode === 'team_size') {
            $this->upgradeByTeamSize();
        } elseif ($mode === 'earnings') {
            $this->upgradeByEarnings();
        }

        $this->info('Super Agent rank upgrades completed.');
        return Command::SUCCESS;
    }

    /**
     * Upgrade ranks based on team size thresholds
     */
    private function upgradeByTeamSize()
    {
        $bronzeThreshold = SystemSetting::get('super_agent_bronze_to_silver_threshold', 10);
        $silverThreshold = SystemSetting::get('super_agent_silver_to_gold_threshold', 50);
        $goldThreshold = SystemSetting::get('super_agent_gold_to_diamond_threshold', 100);

        // Bronze to Silver
        $bronzeSAs = User::where('super_agent_rank', 'bronze')
            ->where('is_super_agent', true)
            ->get();

        foreach ($bronzeSAs as $sa) {
            if ($sa->superAgentTeamSize() >= $bronzeThreshold) {
                $sa->update(['super_agent_rank' => 'silver']);
                $this->info("Upgraded {$sa->username} to Silver (team size: {$sa->superAgentTeamSize()})");
                \Log::info("Super Agent {$sa->id} auto-upgraded to Silver");
            }
        }

        // Silver to Gold
        $silverSAs = User::where('super_agent_rank', 'silver')
            ->where('is_super_agent', true)
            ->get();

        foreach ($silverSAs as $sa) {
            if ($sa->superAgentTeamSize() >= $silverThreshold) {
                $sa->update(['super_agent_rank' => 'gold']);
                $this->info("Upgraded {$sa->username} to Gold (team size: {$sa->superAgentTeamSize()})");
                \Log::info("Super Agent {$sa->id} auto-upgraded to Gold");
            }
        }

        // Gold to Diamond
        $goldSAs = User::where('super_agent_rank', 'gold')
            ->where('is_super_agent', true)
            ->get();

        foreach ($goldSAs as $sa) {
            if ($sa->superAgentTeamSize() >= $goldThreshold) {
                $sa->update(['super_agent_rank' => 'diamond']);
                $this->info("Upgraded {$sa->username} to Diamond (team size: {$sa->superAgentTeamSize()})");
                \Log::info("Super Agent {$sa->id} auto-upgraded to Diamond");
            }
        }
    }

    /**
     * Upgrade ranks based on earnings thresholds
     */
    private function upgradeByEarnings()
    {
        $bronzeThreshold = SystemSetting::get('super_agent_bronze_to_silver_threshold', 50000);
        $silverThreshold = SystemSetting::get('super_agent_silver_to_gold_threshold', 250000);
        $goldThreshold = SystemSetting::get('super_agent_gold_to_diamond_threshold', 1000000);

        // Bronze to Silver
        $bronzeSAs = User::where('super_agent_rank', 'bronze')
            ->where('is_super_agent', true)
            ->get();

        foreach ($bronzeSAs as $sa) {
            if ($sa->superAgentTotalEarnings() >= $bronzeThreshold) {
                $sa->update(['super_agent_rank' => 'silver']);
                $earnings = $sa->superAgentTotalEarnings();
                $this->info("Upgraded {$sa->username} to Silver (earnings: Rs. {$earnings})");
                \Log::info("Super Agent {$sa->id} auto-upgraded to Silver (earnings)");
            }
        }

        // Silver to Gold
        $silverSAs = User::where('super_agent_rank', 'silver')
            ->where('is_super_agent', true)
            ->get();

        foreach ($silverSAs as $sa) {
            if ($sa->superAgentTotalEarnings() >= $silverThreshold) {
                $sa->update(['super_agent_rank' => 'gold']);
                $earnings = $sa->superAgentTotalEarnings();
                $this->info("Upgraded {$sa->username} to Gold (earnings: Rs. {$earnings})");
                \Log::info("Super Agent {$sa->id} auto-upgraded to Gold (earnings)");
            }
        }

        // Gold to Diamond
        $goldSAs = User::where('super_agent_rank', 'gold')
            ->where('is_super_agent', true)
            ->get();

        foreach ($goldSAs as $sa) {
            if ($sa->superAgentTotalEarnings() >= $goldThreshold) {
                $sa->update(['super_agent_rank' => 'diamond']);
                $earnings = $sa->superAgentTotalEarnings();
                $this->info("Upgraded {$sa->username} to Diamond (earnings: Rs. {$earnings})");
                \Log::info("Super Agent {$sa->id} auto-upgraded to Diamond (earnings)");
            }
        }
    }
}
