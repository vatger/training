<?php

namespace App\Jobs\S1;

use App\Models\S1\S1Session;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LockSessionSignups implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Find sessions that need to be locked
        // (signups_lock_at has passed, but session hasn't happened yet)
        $sessionsToLock = S1Session::where('signups_locked', false)
            ->where('signups_lock_at', '<=', now())
            ->where('scheduled_at', '>', now())
            ->get();

        Log::info('LockSessionSignups job running', [
            'sessions_found' => $sessionsToLock->count(),
        ]);

        foreach ($sessionsToLock as $session) {
            try {
                // Lock the session
                $session->update(['signups_locked' => true]);

                Log::info('Session signups locked', [
                    'session_id' => $session->id,
                    'module_id' => $session->module_id,
                    'scheduled_at' => $session->scheduled_at,
                    'total_signups' => $session->signups()->count(),
                ]);

                // Immediately trigger participant selection
                SelectSessionParticipants::dispatch($session);

            } catch (\Exception $e) {
                Log::error('Failed to lock session signups', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}