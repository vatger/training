<?php

namespace App\Jobs\S1;

use App\Services\S1\S1WaitingListService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireWaitingLists implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(S1WaitingListService $waitingListService): void
    {
        Log::info('S1: Processing waiting list expirations');

        [$success, $message, $count] = $waitingListService->deactivateExpiredWaitingLists();

        if ($success) {
            Log::info('S1: Expired waiting lists', [
                'count' => $count,
            ]);
        } else {
            Log::error('S1: Failed to expire waiting lists', [
                'message' => $message,
            ]);
        }
    }
}