<?php

namespace App\Jobs\S1;

use App\Models\S1\S1Session;
use App\Services\S1\S1SessionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LockSessionSignups implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(S1SessionService $sessionService): void
    {
        $sessionsToLock = S1Session::needingLock()->get();

        Log::info('S1: Processing session signups locks', [
            'count' => $sessionsToLock->count(),
        ]);

        foreach ($sessionsToLock as $session) {
            $sessionService->lockSignups($session);
            
            Log::info('S1: Locked signups for session', [
                'session_id' => $session->id,
                'scheduled_at' => $session->scheduled_at,
            ]);
        }
    }
}