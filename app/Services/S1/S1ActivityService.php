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

    public function __construct(MoodleService $moodleService)
    {
        $this->moodleService = $moodleService;
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

        $daysSinceLastActivity = now()->diffInDays($lastActivity);
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

        // Module 2 is special - it's Moodle-based and doesn't have a waiting list
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

        $daysSinceCompletion = now()->diffInDays($moduleCompletion->completed_at);
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

    public function markModule2Inactive(User $user, string $reason): bool
    {
        try {
            $module2 = S1Module::where('sequence_order', 2)->first();
            if (!$module2) {
                return false;
            }

            S1WaitingList::where('user_id', $user->id)
                ->where('module_id', $module2->id)
                ->update(['is_active' => false]);

            Log::info('User marked inactive on Module 2', [
                'user_id' => $user->id,
                'reason' => $reason,
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

            if ($waitingList->needsConfirmation()) {
                $status['warning'] = 'confirmation_overdue';
                $status['action_required'] = true;
                $status['days_overdue'] = now()->diffInDays($waitingList->confirmation_due_at);
            } elseif ($waitingList->isApproachingConfirmationDeadline()) {
                $status['warning'] = 'confirmation_approaching';
                $status['action_required'] = true;
                $status['days_remaining'] = $waitingList->confirmation_due_at->diffInDays(now());
            }

            if ($waitingList->isExpired()) {
                $status['warning'] = 'expired';
                $status['action_required'] = true;
            } elseif ($waitingList->isApproachingExpiry()) {
                $status['warning'] = 'expiry_approaching';
                $status['action_required'] = true;
                $status['days_remaining'] = $waitingList->expires_at->diffInDays(now());
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
            ];
        }

        // Check all module transitions (1->3, 2->3, 3->4)
        $moduleTransitions = [
            1 => 3,  // Module 1 -> Module 3 (skip Module 2 as it's automatic)
            2 => 3,  // Module 2 -> Module 3
            3 => 4,  // Module 3 -> Module 4
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
                    ];
                }
            }
        }

        return $statuses;
    }
}