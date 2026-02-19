<?php

namespace App\Services\S1;

use App\Models\S1\S1Module;
use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1WaitingList;
use App\Models\User;
use App\Services\MoodleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class S1ActivityService
{
    protected MoodleService $moodleService;
    protected S1AttendanceService $attendanceService;

    public function __construct(MoodleService $moodleService, S1AttendanceService $attendanceService)
    {
        $this->moodleService = $moodleService;
        $this->attendanceService = $attendanceService;
    }

    public function checkModule2Activity(User $user): array
    {
        $module2 = S1Module::where('sequence_order', 2)->first();
        if (!$module2) {
            return [false, 'Module 2 not found', null];
        }

        $module1Completion = S1ModuleCompletion::where('user_id', $user->id)
            ->where('module_id', S1Module::where('sequence_order', 1)->value('id'))
            ->first();

        if (!$module1Completion) {
            return [false, 'Module 1 not completed', null];
        }

        $module2Completion = S1ModuleCompletion::where('user_id', $user->id)
            ->where('module_id', $module2->id)
            ->exists();

        if ($module2Completion) {
            return [true, 'Module 2 already completed', ['status' => 'completed']];
        }

        if (!$module2->moodle_quiz_ids || !is_array($module2->moodle_quiz_ids)) {
            return [false, 'No quiz IDs configured', null];
        }

        $lastActivity = $module1Completion->completed_at;
        $completedQuizzes = 0;
        $totalQuizzes = count($module2->moodle_quiz_ids);
        $quizDetails = [];

        foreach ($module2->moodle_quiz_ids as $quizId) {
            try {
                $isCompleted = $this->moodleService->getActivityCompletion(
                    $user->vatsim_id,
                    $quizId
                );

                $quizDetails[] = [
                    'quiz_id' => $quizId,
                    'completed' => $isCompleted,
                ];

                if ($isCompleted) {
                    $completedQuizzes++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to check Module 2 quiz activity', [
                    'user_id' => $user->id,
                    'quiz_id' => $quizId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $daysSinceLastActivity = Carbon::now()->diffInDays($lastActivity, true);
        $isInactive = $daysSinceLastActivity > S1WaitingList::MODULE_2_MAX_INACTIVITY_DAYS;
        $needsWarning = $daysSinceLastActivity > (S1WaitingList::MODULE_2_MAX_INACTIVITY_DAYS - S1WaitingList::WARNING_DAYS_BEFORE_EXPIRY);

        return [true, 'Activity checked', [
            'status' => 'in_progress',
            'completed_quizzes' => $completedQuizzes,
            'total_quizzes' => $totalQuizzes,
            'last_activity' => $lastActivity,
            'days_since_last_activity' => $daysSinceLastActivity,
            'is_inactive' => $isInactive,
            'needs_warning' => $needsWarning && !$isInactive,
            'quiz_details' => $quizDetails,
        ]];
    }

    public function checkNextModuleSignupDeadline(User $user, int $completedModuleSequence): array
    {
        $completedModule = S1Module::where('sequence_order', $completedModuleSequence)->first();
        $nextModule = S1Module::where('sequence_order', $completedModuleSequence + 1)->first();

        if (!$completedModule || !$nextModule) {
            return [false, 'Modules not found', null];
        }

        if ($nextModule->sequence_order === 2) {
            return [false, 'Module 2 does not have a waiting list', null];
        }

        $moduleCompletion = S1ModuleCompletion::where('user_id', $user->id)
            ->where('module_id', $completedModule->id)
            ->first();

        if (!$moduleCompletion) {
            return [false, 'Previous module not completed', null];
        }

        $nextModuleSignup = S1WaitingList::where('user_id', $user->id)
            ->where('module_id', $nextModule->id)
            ->where('is_active', true)
            ->exists();

        $nextModuleCompletion = S1ModuleCompletion::where('user_id', $user->id)
            ->where('module_id', $nextModule->id)
            ->exists();

        if ($nextModuleCompletion) {
            return [true, 'Next module completed', ['status' => 'completed']];
        }

        if ($nextModuleSignup) {
            return [true, 'Already signed up', ['status' => 'signed_up']];
        }

        $daysSinceCompletion = now()->diffInDays($moduleCompletion->completed_at, true);
        $deadlinePassed = $daysSinceCompletion > S1WaitingList::NEXT_MODULE_SIGNUP_DEADLINE_DAYS;
        $needsWarning = $daysSinceCompletion > (S1WaitingList::NEXT_MODULE_SIGNUP_DEADLINE_DAYS - S1WaitingList::WARNING_DAYS_BEFORE_EXPIRY);

        return [true, 'Deadline checked', [
            'status' => 'deadline_active',
            'completed_module_sequence' => $completedModuleSequence,
            'next_module_sequence' => $nextModule->sequence_order,
            'completed_at' => $moduleCompletion->completed_at,
            'days_since_completion' => $daysSinceCompletion,
            'deadline_passed' => $deadlinePassed,
            'needs_warning' => $needsWarning && !$deadlinePassed,
            'days_remaining' => max(0, S1WaitingList::NEXT_MODULE_SIGNUP_DEADLINE_DAYS - $daysSinceCompletion),
        ]];
    }

    public function checkModule3SignupDeadline(User $user): array
    {
        return $this->checkNextModuleSignupDeadline($user, 2);
    }

    /**
     * Mark user as inactive on Module 2
     * This means:
     * 1. Set Module 2 waiting list to inactive (if exists)
     * 2. Unenroll from Module 2 Moodle courses
     * 3. User stays in system but loses Module 2 access
     * 4. User would need mentor intervention to restart Module 2
     */
    public function markModule2Inactive(User $user, string $reason): bool
    {
        try {
            $module2 = S1Module::where('sequence_order', 2)->first();
            if (!$module2) {
                return false;
            }

            // Deactivate Module 2 waiting list (though Module 2 doesn't really use waiting lists)
            S1WaitingList::where('user_id', $user->id)
                ->where('module_id', $module2->id)
                ->update(['is_active' => false]);

            // IMPORTANT: Unenroll from Module 2 Moodle courses
            $this->attendanceService->unenrollUserFromModule2Courses($user->id);

            Log::info('User marked inactive on Module 2 and unenrolled from Moodle', [
                'user_id' => $user->id,
                'reason' => $reason,
                'action' => 'Unenrolled from all Module 2 Moodle courses',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark Module 2 inactive', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark user as completely inactive from S1 training
     * This means:
     * 1. All waiting list entries set to inactive
     * 2. User loses their place in ALL waiting lists
     * 3. User would need to restart from Module 1 or get mentor intervention
     * 4. This is the "removed from training pipeline" scenario
     */
    public function markUserCompletelyInactive(User $user, string $reason): bool
    {
        try {
            // Deactivate ALL waiting lists
            S1WaitingList::where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            Log::info('User completely removed from S1 training pipeline', [
                'user_id' => $user->id,
                'reason' => $reason,
                'action' => 'All waiting lists deactivated - user must restart or contact mentor',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark user completely inactive', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getUserActivityStatus(User $user): array
    {
        $statuses = [];

        $activeWaitingLists = S1WaitingList::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('module')
            ->get();

        foreach ($activeWaitingLists as $waitingList) {
            $status = [
                'module_id' => $waitingList->module_id,
                'module_name' => $waitingList->module->name,
                'type' => 'waiting_list',
            ];

            // Check for 31-day inactivity
            if ($waitingList->isExpired()) {
                $status['warning'] = 'expired';
                $status['action_required'] = true;
                $status['message'] = 'No page visit in 31+ days - will be removed';
            } elseif ($waitingList->isApproachingExpiry()) {
                $status['warning'] = 'expiry_approaching';
                $status['action_required'] = true;
                $daysSinceLastVisit = now()->diffInDays($waitingList->last_confirmed_at, false);
                $status['days_remaining'] = S1WaitingList::INACTIVITY_DAYS - $daysSinceLastVisit;
                $status['message'] = "Visit the training page within {$status['days_remaining']} days to stay active";
            }

            if (isset($status['warning'])) {
                $statuses[] = $status;
            }
        }

        [$success, $message, $module2Status] = $this->checkModule2Activity($user);
        if ($success && isset($module2Status['needs_warning']) && $module2Status['needs_warning']) {
            $statuses[] = [
                'module_id' => S1Module::where('sequence_order', 2)->value('id'),
                'module_name' => 'Module 2',
                'type' => 'module2_activity',
                'warning' => 'inactivity_warning',
                'action_required' => true,
                'days_since_last_activity' => $module2Status['days_since_last_activity'],
                'days_remaining' => max(0, S1WaitingList::MODULE_2_MAX_INACTIVITY_DAYS - $module2Status['days_since_last_activity']),
                'message' => 'Complete a Moodle quiz within ' . max(0, S1WaitingList::MODULE_2_MAX_INACTIVITY_DAYS - $module2Status['days_since_last_activity']) . ' days',
            ];
        }

        $moduleTransitions = [
            1 => 3,
            2 => 3,
            3 => 4,
        ];

        foreach ($moduleTransitions as $completedSeq => $nextSeq) {
            [$success, $message, $signupStatus] = $this->checkNextModuleSignupDeadline($user, $completedSeq);
            
            if ($success && isset($signupStatus['needs_warning']) && $signupStatus['needs_warning']) {
                $nextModule = S1Module::where('sequence_order', $nextSeq)->first();
                if ($nextModule) {
                    $statuses[] = [
                        'module_id' => $nextModule->id,
                        'module_name' => $nextModule->name,
                        'type' => 'next_module_signup_deadline',
                        'warning' => 'signup_deadline_approaching',
                        'action_required' => true,
                        'completed_module_sequence' => $completedSeq,
                        'next_module_sequence' => $nextSeq,
                        'days_remaining' => $signupStatus['days_remaining'],
                        'message' => "Join {$nextModule->name} waiting list within {$signupStatus['days_remaining']} days",
                    ];
                }
            }
        }

        return $statuses;
    }
}