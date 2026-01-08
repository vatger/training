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

class SelectSessionParticipants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $session;

    public function __construct(?S1Session $session = null)
    {
        $this->session = $session;
    }

    public function handle(S1SessionService $sessionService): void
    {
        if ($this->session) {
            $this->selectForSession($this->session, $sessionService);
        } else {
            $lockedSessions = S1Session::where('signups_locked', true)
                ->whereDoesntHave('signups', function ($query) {
                    $query->where('was_selected', true);
                })
                ->where('scheduled_at', '>', now())
                ->get();

            Log::info('SelectSessionParticipants job running', [
                'sessions_found' => $lockedSessions->count(),
            ]);

            foreach ($lockedSessions as $session) {
                $this->selectForSession($session, $sessionService);
            }
        }
    }

    protected function selectForSession(S1Session $session, S1SessionService $sessionService): void
    {
        try {
            [$success, $message, $data] = $sessionService->selectParticipants($session);

            if ($success) {
                Log::info('Session participants selected', [
                    'session_id' => $session->id,
                    'selected_count' => $data['selected'],
                    'rejected_count' => $data['rejected'],
                ]);

                // CLEANUP: Delete non-selected signups
                $deleted = $session->signups()
                    ->where('was_selected', false)
                    ->delete();

                Log::info('Cleaned up non-selected signups', [
                    'session_id' => $session->id,
                    'deleted_count' => $deleted,
                ]);

                // TODO: Send notifications to selected users
                // TODO: Send notifications to rejected users (before deletion!)
            } else {
                Log::warning('Failed to select session participants', [
                    'session_id' => $session->id,
                    'message' => $message,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error selecting session participants', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}