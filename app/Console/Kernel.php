<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\SyncEndorsementActivities::class,
        Commands\RemoveEndorsements::class,
        Commands\SyncUserEndorsements::class,
        Commands\SyncWaitingListActivity::class,
        Commands\CheckRosterStatus::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // === ENDORSEMENT ACTIVITY TRACKING ===
        // Daily check of all endorsements at 3 AM
        $schedule->command('endorsements:sync-activities --batch-size=50')
            ->dailyAt('03:00')
            ->withoutOverlapping(120) // 2-hour timeout
            ->runInBackground();

        // === WAITING LIST ACTIVITY ===
        // Daily check of all waiting list entries at 4 AM
        $schedule->command('waitinglist:sync-activity --batch-size=50')
            ->dailyAt('04:00')
            ->withoutOverlapping(60) // 1-hour timeout
            ->runInBackground();

        // === ENDORSEMENT REMOVALS ===
        // Send removal notifications at 9 AM
        $schedule->command('endorsements:remove --notify')
            ->dailyAt('08:00')
            ->withoutOverlapping();

        // Process actual removals at 10 PM (gives people all day to respond)
        $schedule->command('endorsements:remove')
            ->dailyAt('23:59')
            ->withoutOverlapping();

        // === ROSTER STATUS CHECKS ===
        // Check roster status once daily at 2 AM
        $schedule->command('roster:check --batch-size=50')
            ->dailyAt('02:00')
            ->withoutOverlapping(120); // 2-hour timeout
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}