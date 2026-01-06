<?php

namespace App\Services\S1;

use App\Models\S1\S1Session;
use App\Models\S1\S1SessionSignup;
use App\Models\S1\S1WaitingList;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class S1SessionService
{
    public function createSession(
        int $moduleId,
        int $mentorId,
        Carbon $scheduledAt,
        int $maxTrainees,
        string $language = 'DE',
        ?string $notes = null
    ): array {
        try {
            $signupsLockAt = $scheduledAt->copy()->subHours(48);
            
            $session = S1Session::create([
                'module_id' => $moduleId,
                'mentor_id' => $mentorId,
                'scheduled_at' => $scheduledAt,
                'max_trainees' => $maxTrainees,
                'language' => $language,
                'signups_open' => true,
                'signups_locked' => false,
                'signups_lock_at' => $signupsLockAt,
                'attendance_completed' => false,
                'notes' => $notes,
            ]);

            $this->notifyWaitingListUsers($session);

            return [true, 'Session created successfully', $session];
        } catch (\Exception $e) {
            Log::error('Failed to create S1 session', [
                'error' => $e->getMessage(),
                'module_id' => $moduleId,
            ]);
            return [false, 'Failed to create session', null];
        }
    }

    public function signupForSession(S1Session $session, User $user): array
    {
        if ($session->signups_locked) {
            return [false, 'Signups are locked for this session'];
        }

        if (!$session->signups_open) {
            return [false, 'Signups are closed for this session'];
        }

        $waitingList = S1WaitingList::where('user_id', $user->id)
            ->where('module_id', $session->module_id)
            ->where('is_active', true)
            ->first();

        if (!$waitingList) {
            return [false, 'You must be on the waiting list for this module'];
        }

        if (S1SessionSignup::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->exists()) {
            return [false, 'You are already signed up for this session'];
        }

        $existingSignup = S1SessionSignup::where('user_id', $user->id)
            ->whereHas('session', function ($query) use ($session) {
                $query->where('module_id', $session->module_id)
                    ->where('scheduled_at', '>', now());
            })
            ->first();

        if ($existingSignup) {
            return [false, 'You can only sign up for one session per module at a time. Please cancel your existing signup first.'];
        }

        try {
            S1SessionSignup::create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'waiting_list_id' => $waitingList->id,
                'signed_up_at' => now(),
                'was_selected' => false,
                'notification_sent' => false,
            ]);

            return [true, 'Successfully signed up for session'];
        } catch (\Exception $e) {
            Log::error('Failed to signup for S1 session', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
                'user_id' => $user->id,
            ]);
            return [false, 'Failed to signup for session'];
        }
    }

    public function cancelSignup(S1Session $session, User $user): array
    {
        if ($session->signups_locked) {
            return [false, 'Cannot cancel signup - session is locked'];
        }

        $signup = S1SessionSignup::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$signup) {
            return [false, 'You are not signed up for this session'];
        }

        if ($signup->was_selected) {
            return [false, 'Cannot cancel - you have been selected for this session. Please contact a mentor.'];
        }

        try {
            $signup->delete();
            return [true, 'Signup cancelled successfully'];
        } catch (\Exception $e) {
            Log::error('Failed to cancel S1 session signup', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
                'user_id' => $user->id,
            ]);
            return [false, 'Failed to cancel signup'];
        }
    }

    public function lockSignups(S1Session $session): bool
    {
        if ($session->signups_locked) {
            return true;
        }

        try {
            $session->update(['signups_locked' => true]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to lock S1 session signups', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
            ]);
            return false;
        }
    }

    public function selectParticipants(S1Session $session): array
    {
        if (!$session->signups_locked) {
            return [false, 'Signups must be locked before selecting participants', null];
        }

        try {
            DB::beginTransaction();

            $signups = S1SessionSignup::where('session_id', $session->id)
                ->with('waitingList')
                ->get()
                ->sortBy(function ($signup) {
                    return $signup->waitingList?->joined_at ?? now();
                })
                ->take($session->max_trainees);

            $selectedCount = 0;
            foreach ($signups as $signup) {
                $signup->update([
                    'was_selected' => true,
                    'selected_at' => now(),
                ]);
                $selectedCount++;
            }

            $rejectedSignups = S1SessionSignup::where('session_id', $session->id)
                ->where('was_selected', false)
                ->get();

            DB::commit();

            return [true, "Selected {$selectedCount} participants", [
                'selected' => $selectedCount,
                'rejected' => $rejectedSignups->count(),
            ]];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to select S1 session participants', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
            ]);
            return [false, 'Failed to select participants', null];
        }
    }

    public function getSessionsForCos(?int $moduleId = null): \Illuminate\Support\Collection
    {
        $query = S1Session::with(['module', 'mentor', 'signups'])
            ->orderBy('scheduled_at', 'desc');

        if ($moduleId) {
            $query->where('module_id', $moduleId);
        }

        return $query->get();
    }

    public function getUpcomingSessionsForUser(User $user): \Illuminate\Support\Collection
    {
        $now = now();
        $fortyEightHoursFromNow = $now->copy()->addHours(48);

        return S1Session::with(['module', 'mentor'])
            ->where('scheduled_at', '>', $now)
            ->where(function ($query) use ($fortyEightHoursFromNow, $user) {
                $query->where('scheduled_at', '<=', $fortyEightHoursFromNow)
                    ->whereHas('signups', function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                            ->where('was_selected', true);
                    });
            })
            ->orWhere(function ($query) use ($fortyEightHoursFromNow, $user) {
                $query->where('scheduled_at', '>', $fortyEightHoursFromNow)
                    ->where('signups_locked', false);
            })
            ->orderBy('scheduled_at')
            ->get();
    }

    protected function notifyWaitingListUsers(S1Session $session): void
    {
        $users = S1WaitingList::where('module_id', $session->module_id)
            ->where('is_active', true)
            ->with('user')
            ->get();

        foreach ($users as $waitingList) {
            // TODO: Send notification about new session
        }
    }
}