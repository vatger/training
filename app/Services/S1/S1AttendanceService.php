<?php

namespace App\Services\S1;

use App\Models\S1\S1Session;
use App\Models\S1\S1Attendance;
use App\Models\S1\S1SessionSignup;
use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1WaitingList;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class S1AttendanceService
{
    public function markAttendance(
        S1Session $session,
        User $user,
        string $status,
        ?string $notes = null,
        ?int $markedByMentorId = null,
        bool $spontaneous = false
    ): array {
        if (!in_array($status, ['attended', 'absent', 'excused', 'passed', 'failed'])) {
            return [false, 'Invalid attendance status'];
        }

        try {
            DB::beginTransaction();

            $signup = S1SessionSignup::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first();

            $attendance = S1Attendance::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                ],
                [
                    'signup_id' => $signup?->id,
                    'status' => $status,
                    'notes' => $notes,
                    'marked_by_mentor_id' => $markedByMentorId,
                    'marked_at' => now(),
                    'spontaneous' => $spontaneous,
                ]
            );

            $this->handleAttendanceConsequences($attendance, $session);

            DB::commit();

            return [true, 'Attendance marked successfully', $attendance];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark S1 attendance', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
                'user_id' => $user->id,
            ]);
            return [false, 'Failed to mark attendance', null];
        }
    }

    public function addSpontaneousAttendee(
        S1Session $session,
        User $user,
        string $status = 'attended',
        ?string $notes = null,
        ?int $markedByMentorId = null
    ): array {
        $waitingList = S1WaitingList::where('user_id', $user->id)
            ->where('module_id', $session->module_id)
            ->where('is_active', true)
            ->first();

        if (!$waitingList) {
            return [false, 'User must be on the waiting list for this module'];
        }

        return $this->markAttendance(
            $session,
            $user,
            $status,
            $notes,
            $markedByMentorId,
            true
        );
    }

    public function markAllAttendance(S1Session $session, array $attendanceData, int $markedByMentorId): array
    {
        try {
            DB::beginTransaction();

            $marked = 0;
            $failed = 0;

            foreach ($attendanceData as $data) {
                $user = User::find($data['user_id']);
                if (!$user) {
                    $failed++;
                    continue;
                }

                $result = $this->markAttendance(
                    $session,
                    $user,
                    $data['status'],
                    $data['notes'] ?? null,
                    $markedByMentorId,
                    $data['spontaneous'] ?? false
                );

                if ($result[0]) {
                    $marked++;
                } else {
                    $failed++;
                }
            }

            $session->update(['attendance_completed' => true]);

            DB::commit();

            return [true, "Marked attendance for {$marked} users", [
                'marked' => $marked,
                'failed' => $failed,
            ]];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark bulk S1 attendance', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
            ]);
            return [false, 'Failed to mark attendance', null];
        }
    }

    protected function handleAttendanceConsequences(S1Attendance $attendance, S1Session $session): void
    {
        if ($attendance->shouldCompleteModule()) {
            S1ModuleCompletion::firstOrCreate(
                [
                    'user_id' => $attendance->user_id,
                    'module_id' => $session->module_id,
                ],
                [
                    'completed_at' => now(),
                    'completed_by_mentor_id' => $attendance->marked_by_mentor_id,
                    'was_reset' => false,
                ]
            );

            S1WaitingList::where('user_id', $attendance->user_id)
                ->where('module_id', $session->module_id)
                ->update(['is_active' => false]);
        }

        if ($attendance->shouldLoseWaitingListPosition()) {
            S1WaitingList::where('user_id', $attendance->user_id)
                ->where('module_id', $session->module_id)
                ->update(['is_active' => false]);
        }
    }

    public function getSessionAttendances(S1Session $session): \Illuminate\Support\Collection
    {
        return S1Attendance::where('session_id', $session->id)
            ->with(['user', 'markedByMentor'])
            ->orderBy('marked_at', 'desc')
            ->get();
    }

    public function getUserAttendanceHistory(User $user): \Illuminate\Support\Collection
    {
        return S1Attendance::where('user_id', $user->id)
            ->with(['session.module', 'markedByMentor'])
            ->orderBy('marked_at', 'desc')
            ->get();
    }
}