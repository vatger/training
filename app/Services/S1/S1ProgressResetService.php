<?php

namespace App\Services\S1;

use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1ProgressReset;
use App\Models\S1\S1WaitingList;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class S1ProgressResetService
{
    protected $moodleService;

    public function __construct()
    {
        // TODO: Inject MoodleService when available
    }

    public function resetUserProgress(
        User $user,
        int $resetByMentorId,
        string $reason,
        ?array $specificModules = null
    ): array {
        try {
            DB::beginTransaction();

            $completionsQuery = S1ModuleCompletion::where('user_id', $user->id);

            if ($specificModules) {
                $completionsQuery->whereIn('module_id', $specificModules);
            }

            $completions = $completionsQuery->with('module')->get();

            if ($completions->isEmpty()) {
                return [false, 'No completed modules to reset', null];
            }

            $moodleBackup = $this->backupMoodleData($user, $completions);

            $modulesReset = $completions->map(function ($completion) {
                return [
                    'module_id' => $completion->module_id,
                    'module_name' => $completion->module->name,
                    'completed_at' => $completion->completed_at,
                ];
            })->toArray();

            $completionsQuery->update(['was_reset' => true]);
            $completionsQuery->delete();

            if ($specificModules) {
                S1WaitingList::where('user_id', $user->id)
                    ->whereIn('module_id', $specificModules)
                    ->update(['is_active' => false]);
            } else {
                S1WaitingList::where('user_id', $user->id)
                    ->update(['is_active' => false]);
            }

            $reset = S1ProgressReset::create([
                'user_id' => $user->id,
                'reset_by_mentor_id' => $resetByMentorId,
                'reason' => $reason,
                'modules_reset' => $modulesReset,
                'moodle_data_backup' => $moodleBackup,
                'reset_at' => now(),
            ]);

            $this->resetMoodleProgress($user, $completions);

            DB::commit();

            return [true, 'Progress reset successfully', $reset];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset S1 user progress', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            return [false, 'Failed to reset progress', null];
        }
    }

    protected function backupMoodleData(User $user, $completions): ?array
    {
        try {
            $backup = [];

            foreach ($completions as $completion) {
                $moduleBackup = [
                    'module_id' => $completion->module_id,
                    'courses' => [],
                    'quizzes' => [],
                ];

                if ($completion->module->moodle_course_ids) {
                    foreach ($completion->module->moodle_course_ids as $courseId) {
                        // TODO: Call Moodle API to get course grades
                        $moduleBackup['courses'][$courseId] = [
                            'course_id' => $courseId,
                            'grade' => null,
                            'completion_status' => null,
                        ];
                    }
                }

                if ($completion->module->moodle_quiz_ids) {
                    foreach ($completion->module->moodle_quiz_ids as $quizId) {
                        // TODO: Call Moodle API to get quiz attempts
                        $moduleBackup['quizzes'][$quizId] = [
                            'quiz_id' => $quizId,
                            'attempts' => [],
                        ];
                    }
                }

                $backup[] = $moduleBackup;
            }

            return $backup;
        } catch (\Exception $e) {
            Log::error('Failed to backup Moodle data', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            return null;
        }
    }

    protected function resetMoodleProgress(User $user, $completions): void
    {
        try {
            foreach ($completions as $completion) {
                if ($completion->module->moodle_course_ids) {
                    foreach ($completion->module->moodle_course_ids as $courseId) {
                        // TODO: Call Moodle API to reset course progress
                    }
                }

                if ($completion->module->moodle_quiz_ids) {
                    foreach ($completion->module->moodle_quiz_ids as $quizId) {
                        // TODO: Call Moodle API to clear quiz attempts
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to reset Moodle progress', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }
    }

    public function getUserResetHistory(User $user): \Illuminate\Support\Collection
    {
        return S1ProgressReset::where('user_id', $user->id)
            ->with('resetByMentor')
            ->orderBy('reset_at', 'desc')
            ->get();
    }
}