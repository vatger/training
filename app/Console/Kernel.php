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
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}