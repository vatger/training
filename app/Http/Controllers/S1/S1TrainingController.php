<?php

namespace App\Http\Controllers\S1;

use App\Http\Controllers\Controller;
use App\Models\S1\S1Module;
use App\Models\S1\S1WaitingList;
use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1Session;
use App\Models\S1\S1SessionSignup;
use App\Services\MoodleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class S1TrainingController extends Controller
{
    protected MoodleService $moodleService;

    public function __construct(MoodleService $moodleService)
    {
        $this->moodleService = $moodleService;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        if (!$user->isVatsimUser()) {
            return Inertia::render('s1/training', [
                'isVatsimUser' => false,
                'currentStep' => null,
                'progress' => null,
                'modules' => [],
            ]);
        }

        $modules = S1Module::active()->ordered()->get();
        
        $userProgress = $this->getUserProgress($user, $modules);
        $currentStep = $this->determineCurrentStep($user, $userProgress);
        
        return Inertia::render('s1/training', [
            'isVatsimUser' => true,
            'currentStep' => $currentStep,
            'progress' => $userProgress,
            'modules' => $modules->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'sequence_order' => $m->sequence_order,
            ]),
        ]);
    }

    protected function getUserProgress($user, $modules)
    {
        $progress = [
            'overallPercentage' => 0,
            'currentStepNumber' => 1,
            'totalSteps' => 5,
            'modules' => [],
            'canUpgrade' => false,
        ];

        $isActivelyOnModule2 = false;
        foreach ($modules as $module) {
            if ($module->sequence_order === 2) {
                $module1 = S1Module::where('sequence_order', 1)->first();
                $hasCompletedModule1 = false;

                if ($module1) {
                    $hasCompletedModule1 = S1ModuleCompletion::where('user_id', $user->id)
                        ->where('module_id', $module1->id)
                        ->exists();
                }

                $hasCompletedModule2 = S1ModuleCompletion::where('user_id', $user->id)
                    ->where('module_id', $module->id)
                    ->exists();

                $isActivelyOnModule2 = $hasCompletedModule1 && !$hasCompletedModule2;
                break;
            }
        }

        foreach ($modules as $module) {
            $completion = S1ModuleCompletion::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->first();

            $waitingList = S1WaitingList::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('is_active', true)
                ->first();

            $moduleData = [
                'id' => $module->id,
                'name' => $module->name,
                'sequence_order' => $module->sequence_order,
                'status' => $completion ? 'completed' : ($waitingList ? 'waiting' : 'locked'),
                'completed_at' => $completion?->completed_at?->format('Y-m-d'),
                'waiting_list' => null,
                'available_sessions' => [],
                'quiz_completion' => null,
            ];

            if ($waitingList) {
                $position = S1WaitingList::where('module_id', $module->id)
                    ->where('is_active', true)
                    ->where('joined_at', '<', $waitingList->joined_at)
                    ->count() + 1;

                $totalWaiting = S1WaitingList::where('module_id', $module->id)
                    ->where('is_active', true)
                    ->count();

                $needsConfirmation = $waitingList->confirmation_due_at && 
                    $waitingList->confirmation_due_at->isPast();

                $moduleData['waiting_list'] = [
                    'id' => $waitingList->id,
                    'position' => $position,
                    'total_waiting' => $totalWaiting,
                    'joined_at' => $waitingList->joined_at->format('Y-m-d'),
                    'expires_at' => $waitingList->expires_at?->format('Y-m-d'),
                    'needs_confirmation' => $needsConfirmation,
                    'confirmation_due_at' => $waitingList->confirmation_due_at?->format('Y-m-d'),
                ];

                $hasSelectedSession = S1SessionSignup::where('user_id', $user->id)
                    ->where('was_selected', true)
                    ->whereHas('session', function($q) use ($module) {
                        $q->where('module_id', $module->id)
                          ->where('scheduled_at', '>', now());
                    })
                    ->exists();

                $hasAnySignup = S1SessionSignup::where('user_id', $user->id)
                    ->whereHas('session', function($q) use ($module) {
                        $q->where('module_id', $module->id)
                          ->where('scheduled_at', '>', now());
                    })
                    ->exists();

                // Always show all available sessions, but mark which ones the user is signed up for
                $availableSessions = S1Session::where('module_id', $module->id)
                    ->where('scheduled_at', '>', now())
                    ->where(function ($q) use ($user) {
                        $q->where('signups_open', true)
                            ->orWhereHas('signups', function ($sq) use ($user) {
                                $sq->where('user_id', $user->id);
                            });
                    })
                    ->with(['mentor'])
                    ->orderBy('scheduled_at')
                    ->get();

                $moduleData['has_selected_session'] = $hasSelectedSession;
                $moduleData['has_any_signup'] = $hasAnySignup;

                $moduleData['available_sessions'] = $availableSessions->map(function ($session) use ($user) {
                    $signup = S1SessionSignup::where('session_id', $session->id)
                        ->where('user_id', $user->id)
                        ->first();

                    $totalSignups = S1SessionSignup::where('session_id', $session->id)->count();
                    $selectedCount = S1SessionSignup::where('session_id', $session->id)
                        ->where('was_selected', true)
                        ->count();

                    return [
                        'id' => $session->id,
                        'scheduled_at' => $session->scheduled_at->format('Y-m-d H:i'),
                        'mentor_name' => $session->mentor->name,
                        'language' => $session->language,
                        'max_trainees' => $session->max_trainees,
                        'total_signups' => $totalSignups,
                        'available_spots' => max(0, $session->max_trainees - $selectedCount),
                        'signups_locked' => $session->signups_locked,
                        'notes' => $session->notes,
                        'user_signed_up' => $signup !== null,
                        'user_selected' => $signup?->was_selected ?? false,
                    ];
                });
            }

            // Only check Module 2 Moodle completion if user is actively on Module 2
            if ($module->sequence_order === 2 && $isActivelyOnModule2) {
                $module1 = S1Module::where('sequence_order', 1)->first();
                $hasCompletedModule1 = false;

                if ($module1) {
                    $hasCompletedModule1 = S1ModuleCompletion::where('user_id', $user->id)
                        ->where('module_id', $module1->id)
                        ->exists();
                }

                if ($hasCompletedModule1 && !$completion) {
                    $moduleData['status'] = 'waiting';
                }

                if ($module->moodle_quiz_ids && is_array($module->moodle_quiz_ids)) {
                    $quizIds = $module->moodle_quiz_ids;

                    $courseNames = [
                        'Basics of controlling',
                        'ATD Delivery',
                        'ATD Ground',
                        'ATD Tower',
                    ];

                    $completed = [];
                    $total = count($quizIds);
                    $isEnrolled = true;
                    $quizzes = [];

                    foreach ($quizIds as $index => $quizId) {
                        try {
                            $isCompleted = $this->moodleService->getActivityCompletion(
                                $user->vatsim_id,
                                $quizId
                            );

                            if ($isCompleted) {
                                $completed[] = $quizId;
                            }

                            $quizzes[] = [
                                'id' => $quizId,
                                'name' => $courseNames[$index] ?? "Course " . ($index + 1),
                                'completed' => $isCompleted,
                                'url' => 'https://moodle.vatsim-germany.org/mod/quiz/view.php?id=' . $quizId,
                            ];
                        } catch (\Exception $e) {
                            \Log::warning('Failed to check Moodle activity completion', [
                                'user_id' => $user->id,
                                'vatsim_id' => $user->vatsim_id,
                                'quiz_id' => $quizId,
                                'error' => $e->getMessage(),
                            ]);
                            $isEnrolled = false;
                            break;
                        }
                    }

                    $moduleData['quiz_completion'] = [
                        'completed' => count($completed),
                        'total' => $total,
                        'percentage' => $total > 0 ? round((count($completed) / $total) * 100) : 0,
                        'quizzes' => $quizzes,
                        'is_enrolled' => $isEnrolled,
                    ];
                }

                $progress['modules'][] = $moduleData;
                continue;
            }

            $progress['modules'][] = $moduleData;
        }

        $completedModules = count(array_filter($progress['modules'], fn($m) => $m['status'] === 'completed'));

        $module2Progress = 0;
        foreach ($progress['modules'] as $module) {
            if ($module['sequence_order'] === 2 && $module['quiz_completion'] && $module['quiz_completion']['total'] > 0) {
                $module2Progress = $module['quiz_completion']['completed'] / $module['quiz_completion']['total'];
                break;
            }
        }

        $totalProgress = $completedModules + $module2Progress;
        $progress['overallPercentage'] = round(($totalProgress / count($modules)) * 100);

        $allModulesCompleted = $completedModules === count($modules);
        if ($allModulesCompleted) {
            $progress['canUpgrade'] = $this->canUserUpgrade($user);
        }

        return $progress;
    }

    protected function determineCurrentStep($user, $progress)
    {
        foreach ($progress['modules'] as $module) {
            if ($module['status'] === 'locked' && $module['sequence_order'] === 1) {
                return [
                    'type' => 'join_waiting_list',
                    'module' => $module,
                    'title' => 'Join Module 1 Waiting List',
                    'description' => 'You need to join the Module 1 waiting list to begin your S1 training journey.',
                    'action' => 'Join Waiting List',
                    'action_type' => 'post',
                    'action_data' => ['module_id' => $module['id']],
                ];
            }

            if ($module['status'] === 'waiting') {
                if ($module['sequence_order'] === 2 && $module['quiz_completion']) {
                    if (!$module['quiz_completion']['is_enrolled']) {
                        return [
                            'type' => 'module2_not_enrolled',
                            'module' => $module,
                            'title' => 'Module 2 - Enrollment Required',
                            'description' => 'Contact your Module 1 mentor to get enrolled.',
                            'action' => null,
                        ];
                    }

                    if ($module['quiz_completion']['completed'] < $module['quiz_completion']['total']) {
                        return [
                            'type' => 'complete_quizzes',
                            'module' => $module,
                            'title' => 'Complete Module 2 Courses',
                            'description' => sprintf(
                                'Complete all %d courses. You have %d out of %d done.',
                                $module['quiz_completion']['total'],
                                $module['quiz_completion']['completed'],
                                $module['quiz_completion']['total']
                            ),
                            'progress' => $module['quiz_completion']['percentage'],
                            'action' => 'Go to Moodle',
                            'action_type' => 'external',
                            'action_url' => 'https://moodle.vatsim-germany.org/',
                        ];
                    }
                }

                if (!empty($module['available_sessions'])) {
                    $userSession = collect($module['available_sessions'])->firstWhere('user_signed_up', true);
                    
                    if ($userSession && $userSession['user_selected']) {
                        return [
                            'type' => 'confirmed_session',
                            'module' => $module,
                            'session' => $userSession,
                            'title' => sprintf('%s Session Confirmed', $module['name']),
                            'description' => sprintf('Session on %s', $userSession['scheduled_at']),
                        ];
                    }

                    if ($userSession) {
                        return [
                            'type' => 'pending_selection',
                            'module' => $module,
                            'session' => $userSession,
                            'title' => 'Waiting for Session Selection',
                            'description' => sprintf('Session on %s', $userSession['scheduled_at']),
                        ];
                    }

                    if (count($module['available_sessions']) > 0) {
                        $nextSession = $module['available_sessions'][0];
                        return [
                            'type' => 'signup_available',
                            'module' => $module,
                            'session' => $nextSession,
                            'title' => sprintf('%s Session Available', $module['name']),
                            'description' => sprintf('Session on %s', $nextSession['scheduled_at']),
                            'action' => 'Sign Up for Session',
                            'action_type' => 'post',
                            'action_data' => ['session_id' => $nextSession['id']],
                            'waiting_position' => $module['waiting_list']['position'],
                        ];
                    }
                }

                return [
                    'type' => 'on_waiting_list',
                    'module' => $module,
                    'title' => sprintf('On %s Waiting List', $module['name']),
                    'description' => sprintf('Position #%d of %d', $module['waiting_list']['position'], $module['waiting_list']['total_waiting']),
                    'needs_confirmation' => $module['waiting_list']['needs_confirmation'],
                    'confirmation_data' => $module['waiting_list']['needs_confirmation'] ? ['waiting_list_id' => $module['waiting_list']['id']] : null,
                    'action' => 'Leave Waiting List',
                    'action_type' => 'post',
                    'action_data' => ['module_id' => $module['id']],
                    'action_variant' => 'destructive',
                ];
            }

            if ($module['status'] === 'locked' && $module['sequence_order'] > 1) {
                $previousModule = collect($progress['modules'])->firstWhere('sequence_order', $module['sequence_order'] - 1);
                if ($previousModule && $previousModule['status'] !== 'completed') {
                    continue;
                }

                return [
                    'type' => 'join_waiting_list',
                    'module' => $module,
                    'title' => sprintf('Join %s Waiting List', $module['name']),
                    'description' => 'Previous module completed! Join the waiting list to continue.',
                    'action' => 'Join Waiting List',
                    'action_type' => 'post',
                    'action_data' => ['module_id' => $module['id']],
                ];
            }
        }

        if ($progress['canUpgrade']) {
            return [
                'type' => 'request_upgrade',
                'title' => 'Request S1 Rating',
                'description' => 'All modules completed!',
                'action' => 'Request Rating Upgrade',
                'action_type' => 'post',
                'action_data' => [],
            ];
        }

        return [
            'type' => 'completed',
            'title' => 'Training Complete',
            'description' => 'You have completed all S1 training modules!',
        ];
    }

    protected function canUserUpgrade($user): bool
    {
        return false;
    }
}