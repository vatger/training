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
        $schedule->command('endorsements:sync-activities')
            ->dailyAt('03:00')
            ->withoutOverlapping(120)
            ->runInBackground();

        // === WAITING LIST ACTIVITY ===
        $schedule->command('waitinglists:sync-activities')
            ->dailyAt('04:00')
            ->withoutOverlapping(60)
            ->runInBackground();

        // === ENDORSEMENT REMOVALS ===
        $schedule->command('endorsements:remove')
            ->dailyAt('08:00')
            ->withoutOverlapping();

        // === ROSTER STATUS CHECKS ===
        $schedule->command('roster:check')
            ->dailyAt('02:00')
            ->withoutOverlapping(120);
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}