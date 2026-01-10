<?php

namespace App\Jobs\S1;

use App\Models\S1\S1WaitingList;
use App\Services\S1\S1NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWaitingListActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(S1NotificationService $notificationService): void
    {
        Log::info('S1: Processing waiting list activity');

        $needingWarning = S1WaitingList::needingWarning()
            ->with('user', 'module')
            ->get();

        $warningsSent = 0;
        foreach ($needingWarning as $waitingList) {
            try {
                $waitingList->markWarningAsSent();
                $warningsSent++;
                
                Log::info('Activity warning marked for waiting list', [
                    'waiting_list_id' => $waitingList->id,
                    'user_id' => $waitingList->user_id,
                    'module_id' => $waitingList->module_id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to mark activity warning', [
                    'waiting_list_id' => $waitingList->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('S1: Activity warnings processed', [
            'warnings_sent' => $warningsSent,
        ]);
    }
}