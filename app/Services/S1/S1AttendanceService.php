<?php

namespace App\Services\S1;

use App\Models\S1\S1Session;
use App\Models\S1\S1Attendance;
use App\Models\S1\S1SessionSignup;
use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1WaitingList;
use App\Models\S1\S1Module;
use App\Models\User;
use App\Services\MoodleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class S1AttendanceService
{
    protected MoodleService $moodleService;

    public function __construct(MoodleService $moodleService)
    {
        $this->moodleService = $moodleService;
    }

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
            $moduleCompletion = S1ModuleCompletion::firstOrCreate(
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

            $module = S1Module::find($session->module_id);
            if ($module && $module->sequence_order === 1) {
                $this->enrollUserInModule2Courses($attendance->user_id);
            }
        }

        if ($attendance->shouldLoseWaitingListPosition()) {
            S1WaitingList::where('user_id', $attendance->user_id)
                ->where('module_id', $session->module_id)
                ->update(['is_active' => false]);
        }
    }

    protected function enrollUserInModule2Courses(int $userId): void
    {
        try {
            $user = User::find($userId);
            if (!$user || !$user->vatsim_id) {
                Log::warning('Cannot enroll user in Module 2: User not found or missing VATSIM ID', [
                    'user_id' => $userId,
                ]);
                return;
            }

            $module2 = S1Module::where('sequence_order', 2)->first();
            if (!$module2) {
                Log::warning('Module 2 not found');
                return;
            }

            if (!$module2->moodle_course_ids || !is_array($module2->moodle_course_ids)) {
                Log::warning('Module 2 has no Moodle course IDs configured');
                return;
            }

            foreach ($module2->moodle_course_ids as $courseId) {
                $this->moodleService->enrollUser($user->vatsim_id, $courseId);
            }

            Log::info('User enrolled in Module 2 Moodle courses', [
                'user_id' => $userId,
                'vatsim_id' => $user->vatsim_id,
                'course_ids' => $module2->moodle_course_ids,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to enroll user in Module 2 Moodle courses', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Unenroll user from Module 2 Moodle courses
     * Called when user is removed from Module 2 for inactivity
     */
    public function unenrollUserFromModule2Courses(int $userId): void
    {
        try {
            $user = User::find($userId);
            if (!$user || !$user->vatsim_id) {
                Log::warning('Cannot unenroll user from Module 2: User not found or missing VATSIM ID', [
                    'user_id' => $userId,
                ]);
                return;
            }

            $module2 = S1Module::where('sequence_order', 2)->first();
            if (!$module2) {
                Log::warning('Module 2 not found');
                return;
            }

            if (!$module2->moodle_course_ids || !is_array($module2->moodle_course_ids)) {
                Log::warning('Module 2 has no Moodle course IDs configured');
                return;
            }

            foreach ($module2->moodle_course_ids as $courseId) {
                $this->moodleService->unenrollUser($user->vatsim_id, $courseId);
            }

            Log::info('User unenrolled from Module 2 Moodle courses due to inactivity', [
                'user_id' => $userId,
                'vatsim_id' => $user->vatsim_id,
                'course_ids' => $module2->moodle_course_ids,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to unenroll user from Module 2 Moodle courses', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
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