<?php

namespace App\Jobs\S1;

use App\Services\S1\S1NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendConfirmationReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(S1NotificationService $notificationService): void
    {
        Log::info('S1: Sending confirmation reminders');

        [$success, $message] = $notificationService->sendConfirmationReminders();

        if ($success) {
            Log::info('S1: ' . $message);
        } else {
            Log::error('S1: Failed to send confirmation reminders', [
                'message' => $message,
            ]);
        }

        [$success, $message] = $notificationService->sendExpiryWarnings();

        if ($success) {
            Log::info('S1: ' . $message);
        } else {
            Log::error('S1: Failed to send expiry warnings', [
                'message' => $message,
            ]);
        }
    }
}