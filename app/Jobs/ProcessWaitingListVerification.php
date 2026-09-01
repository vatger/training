<?php

namespace App\Jobs;

use App\Domain\WaitingList\Actions\ProcessMonthlyWaitingListVerification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessWaitingListVerification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function handle(ProcessMonthlyWaitingListVerification $verification): void
    {
        try {
            $verification->execute();
        } catch (\Throwable $e) {
            Log::error('Waiting list interest verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
