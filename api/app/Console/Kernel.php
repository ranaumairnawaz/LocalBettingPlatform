<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run Super Agent rank upgrade check nightly at 2:00 AM
        $schedule->command('super-agent:upgrade-ranks')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Super Agent rank upgrade command completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Super Agent rank upgrade command failed');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
