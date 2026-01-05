<?php

namespace App\Jobs\S1;

use App\Models\S1\S1Session;
use App\Services\S1\S1SessionService;
use App\Services\S1\S1NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SelectSessionParticipants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(
        S1SessionService $sessionService,
        S1NotificationService $notificationService
    ): void {
        $lockedSessions = S1Session::where('signups_locked', true)
            ->whereDoesntHave('signups', function ($query) {
                $query->where('was_selected', true);
            })
            ->where('scheduled_at', '>', now())
            ->get();

        Log::info('S1: Processing participant selection', [
            'count' => $lockedSessions->count(),
        ]);

        foreach ($lockedSessions as $session) {
            [$success, $message, $data] = $sessionService->selectParticipants($session);
            
            if ($success) {
                Log::info('S1: Selected participants for session', [
                    'session_id' => $session->id,
                    'selected' => $data['selected'],
                    'rejected' => $data['rejected'],
                ]);

                $notificationService->sendSessionSelectionNotifications($session);
            } else {
                Log::error('S1: Failed to select participants', [
                    'session_id' => $session->id,
                    'message' => $message,
                ]);
            }
        }
    }
}