<?php

namespace App\Http\Controllers\S1;

use App\Http\Controllers\Controller;
use App\Models\S1\S1Module;
use App\Models\S1\S1WaitingList;
use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1Session;
use App\Models\S1\S1SessionSignup;
use App\Models\S1\S1SessionAttendance;
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

                // Check if user has a selected session for this module
                $hasSelectedSession = S1SessionSignup::where('user_id', $user->id)
                    ->where('was_selected', true)
                    ->whereHas('session', function($q) use ($module) {
                        $q->where('module_id', $module->id)
                          ->where('scheduled_at', '>', now());
                    })
                    ->exists();

                // Check if user has ANY signup (selected or not) for this module
                $hasAnySignup = S1SessionSignup::where('user_id', $user->id)
                    ->whereHas('session', function($q) use ($module) {
                        $q->where('module_id', $module->id)
                          ->where('scheduled_at', '>', now());
                    })
                    ->exists();

                // If user has ANY signup (selected or just signed up), only show THEIR session
                // Otherwise show all open sessions
                if ($hasAnySignup) {
                    // Only show the session(s) they're signed up for
                    $availableSessions = S1Session::where('module_id', $module->id)
                        ->where('scheduled_at', '>', now())
                        ->whereHas('signups', function($q) use ($user) {
                            $q->where('user_id', $user->id);
                        })
                        ->with(['mentor'])
                        ->orderBy('scheduled_at')
                        ->get();
                } else {
                    // Show all open sessions (user hasn't signed up for any)
                    $availableSessions = S1Session::where('module_id', $module->id)
                        ->where('scheduled_at', '>', now())
                        ->where('signups_open', true)
                        ->where('signups_locked', false)
                        ->with(['mentor'])
                        ->orderBy('scheduled_at')
                        ->get();
                }

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
                        'user_signed_up' => $signup !== null,
                        'user_selected' => $signup?->was_selected ?? false,
                    ];
                });
            }

            if ($module->name === 'Module 2' && $module->moodle_quiz_ids) {
                $quizIds = $module->moodle_quiz_ids;
                $completed = [];
                $total = count($quizIds);

                foreach ($quizIds as $quizId) {
                    $isCompleted = $this->moodleService->getCourseCompletion(
                        $user->vatsim_id,
                        $quizId
                    );
                    if ($isCompleted) {
                        $completed[] = $quizId;
                    }
                }

                $moduleData['quiz_completion'] = [
                    'completed' => count($completed),
                    'total' => $total,
                    'percentage' => $total > 0 ? round((count($completed) / $total) * 100) : 0,
                    'quizzes' => array_map(function($quizId) use ($completed) {
                        return [
                            'id' => $quizId,
                            'completed' => in_array($quizId, $completed),
                        ];
                    }, $quizIds),
                ];
            }

            $progress['modules'][] = $moduleData;
        }

        $completedModules = count(array_filter($progress['modules'], fn($m) => $m['status'] === 'completed'));
        $progress['overallPercentage'] = round(($completedModules / count($modules)) * 100);

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
                    'description' => 'You need to join the Module 1 waiting list to begin your S1 training journey. Once you join, you\'ll receive notifications about upcoming training sessions.',
                    'action' => 'Join Waiting List',
                    'action_type' => 'post',
                    'action_data' => [
                        'module_id' => $module['id'],
                    ],
                ];
            }

            if ($module['status'] === 'waiting') {
                if ($module['name'] === 'Module 2') {
                    if ($module['quiz_completion'] && $module['quiz_completion']['completed'] < $module['quiz_completion']['total']) {
                        return [
                            'type' => 'complete_quizzes',
                            'module' => $module,
                            'title' => 'Complete Module 2 Quizzes',
                            'description' => sprintf(
                                'Complete all %d quizzes on the Moodle platform to finish Module 2. You have completed %d out of %d quizzes.',
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
                            'description' => sprintf(
                                'Your spot in the %s session on %s is confirmed. Make sure you\'re prepared and available at the scheduled time.',
                                $module['name'],
                                $userSession['scheduled_at']
                            ),
                        ];
                    }

                    if ($userSession) {
                        return [
                            'type' => 'pending_selection',
                            'module' => $module,
                            'session' => $userSession,
                            'title' => 'Waiting for Session Selection',
                            'description' => sprintf(
                                'You\'ve signed up for a %s session on %s. You\'ll be notified once participants are selected (typically 48 hours before the session).',
                                $module['name'],
                                \Carbon\Carbon::parse($userSession['scheduled_at'])->format('d.m.Y \a\t H:i')
                            ),
                        ];
                    }

                    if (count($module['available_sessions']) > 0) {
                        $nextSession = $module['available_sessions'][0];
                        return [
                            'type' => 'signup_available',
                            'module' => $module,
                            'session' => $nextSession,
                            'title' => sprintf('%s Session Available', $module['name']),
                            'description' => sprintf(
                                'A training session is available on %s. Sign up now to reserve your spot. Note: Only the top %d people on the waiting list will be selected.',
                                $nextSession['scheduled_at'],
                                $nextSession['max_trainees']
                            ),
                            'action' => 'Sign Up for Session',
                            'action_type' => 'post',
                            'action_data' => [
                                'session_id' => $nextSession['id'],
                            ],
                            'waiting_position' => $module['waiting_list']['position'],
                        ];
                    }
                }

                return [
                    'type' => 'on_waiting_list',
                    'module' => $module,
                    'title' => sprintf('On %s Waiting List', $module['name']),
                    'description' => sprintf(
                        'You\'re currently position #%d of %d on the %s waiting list. You\'ll be notified when training sessions become available.',
                        $module['waiting_list']['position'],
                        $module['waiting_list']['total_waiting'],
                        $module['name']
                    ),
                    'needs_confirmation' => $module['waiting_list']['needs_confirmation'],
                    'confirmation_data' => $module['waiting_list']['needs_confirmation'] ? [
                        'waiting_list_id' => $module['waiting_list']['id'],
                    ] : null,
                    'action' => 'Leave Waiting List',
                    'action_type' => 'post',
                    'action_data' => [
                        'module_id' => $module['id'],
                    ],
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
                    'description' => sprintf(
                        'You\'ve completed the previous module! Join the %s waiting list to continue your training.',
                        $module['name']
                    ),
                    'action' => 'Join Waiting List',
                    'action_type' => 'post',
                    'action_data' => [
                        'module_id' => $module['id'],
                    ],
                ];
            }
        }

        if ($progress['canUpgrade']) {
            return [
                'type' => 'request_upgrade',
                'title' => 'Request S1 Rating',
                'description' => 'Congratulations! You\'ve completed all training modules. You can now request your S1 rating upgrade.',
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