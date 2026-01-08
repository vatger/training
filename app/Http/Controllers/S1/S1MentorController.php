<?php

namespace App\Http\Controllers\S1;

use App\Http\Controllers\Controller;
use App\Models\S1\S1Module;
use App\Models\S1\S1Session;
use App\Models\S1\S1Attendance;
use App\Models\S1\S1SessionSignup;
use App\Models\S1\S1WaitingList;
use App\Models\S1\S1ModuleCompletion;
use App\Services\S1\S1AttendanceService;
use App\Services\S1\S1SessionService;
use App\Services\MoodleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class S1MentorController extends Controller
{
    protected $sessionService;
    protected $attendanceService;
    protected $moodleService;

    public function __construct(
        S1SessionService $sessionService,
        S1AttendanceService $attendanceService,
        MoodleService $moodleService
    ) {
        $this->sessionService = $sessionService;
        $this->attendanceService = $attendanceService;
        $this->moodleService = $moodleService;
    }

    public function index(): Response
    {
        $user = auth()->user();

        $modules = S1Module::orderBy('sequence_order')->get();

        $upcomingSessions = S1Session::with(['module', 'mentor', 'signups.user', 'signups.waitingList'])
            ->where('mentor_id', $user->id)
            ->where(function ($query) {
                $query->where('scheduled_at', '>', now())
                    ->orWhere(function ($q) {
                        $q->where('scheduled_at', '<=', now())
                            ->where('scheduled_at', '>', now()->subHours(24))
                            ->where('attendance_completed', false);
                    });
            })
            ->orderBy('scheduled_at')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'module_name' => $session->module->name,
                    'module_id' => $session->module_id,
                    'scheduled_at' => $session->scheduled_at->toIso8601String(),
                    'max_trainees' => $session->max_trainees,
                    'language' => $session->language,
                    'signups_open' => $session->signups_open,
                    'signups_locked' => $session->signups_locked,
                    'signups_lock_at' => $session->signups_lock_at?->toIso8601String(),
                    'attendance_completed' => $session->attendance_completed,
                    'total_signups' => $session->signups->count(),
                    'selected_count' => $session->signups->where('was_selected', true)->count(),
                    'notes' => $session->notes,
                    'is_past' => $session->scheduled_at <= now(),
                    'participants' => $session->signups()
                        ->where('was_selected', true)
                        ->with(['user'])
                        ->get()
                        ->map(function ($signup) {
                            $attendance = S1Attendance::where('session_id', $signup->session_id)
                                ->where('user_id', $signup->user_id)
                                ->first();
                            
                            return [
                                'id' => $signup->id,
                                'user_id' => $signup->user_id,
                                'user_name' => $signup->user->name,
                                'user_vatsim_id' => $signup->user->vatsim_id,
                                'waiting_list_position' => $signup->waitingList?->position_in_queue,
                                'attendance' => $attendance ? [
                                    'id' => $attendance->id,
                                    'status' => $attendance->status,
                                    'notes' => $attendance->notes,
                                ] : null,
                            ];
                        }),
                ];
            });

        $pastSessions = S1Session::with(['module', 'signups', 'attendances.user'])
            ->where('mentor_id', $user->id)
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'module_name' => $session->module->name,
                    'scheduled_at' => $session->scheduled_at->toIso8601String(),
                    'max_trainees' => $session->max_trainees,
                    'language' => $session->language,
                    'attendance_completed' => $session->attendance_completed,
                    'notes' => $session->notes,
                    'participants_count' => $session->signups()->where('was_selected', true)->count(),
                    'participants' => $session->attendances->map(function ($attendance) {
                        return [
                            'id' => $attendance->id,
                            'user_id' => $attendance->user_id,
                            'user_name' => $attendance->user->name,
                            'user_vatsim_id' => $attendance->user->vatsim_id,
                            'status' => $attendance->status,
                            'notes' => $attendance->notes,
                            'marked_at' => $attendance->marked_at?->toIso8601String(),
                            'spontaneous' => $attendance->spontaneous,
                        ];
                    }),
                ];
            });

        $waitingLists = S1Module::with(['waitingLists' => function ($query) {
            $query->where('is_active', true)
                ->orderBy('joined_at')
                ->with('user');
        }])
            ->where('sequence_order', '!=', 2)
            ->orderBy('sequence_order')
            ->get()
            ->map(function ($module) {
                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'sequence_order' => $module->sequence_order,
                    'waiting_count' => $module->waitingLists->count(),
                    'users' => $module->waitingLists->map(function ($wl, $index) {
                        return [
                            'id' => $wl->id,
                            'user_id' => $wl->user_id,
                            'user_name' => $wl->user->name,
                            'user_vatsim_id' => $wl->user->vatsim_id,
                            'position' => $index + 1,
                            'joined_at' => $wl->joined_at->toIso8601String(),
                            'last_confirmed_at' => $wl->last_confirmed_at?->toIso8601String(),
                            'needs_confirmation' => $wl->confirmation_due_at?->isPast(),
                        ];
                    }),
                ];
            });

        $module2 = S1Module::where('sequence_order', 2)->first();
        $module2Users = [];

        if ($module2) {
            $completedModule1 = S1ModuleCompletion::where('module_id', S1Module::where('sequence_order', 1)->value('id'))
                ->pluck('user_id');

            $completedModule2 = S1ModuleCompletion::where('module_id', $module2->id)
                ->pluck('user_id');

            $activeModule2UserIds = $completedModule1->diff($completedModule2);

            $module2Users = \App\Models\User::whereIn('id', $activeModule2UserIds)
                ->get()
                ->map(function ($user) use ($module2) {
                    $quizCompletion = $this->getUserModule2DetailedProgress($user, $module2);

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'vatsim_id' => $user->vatsim_id,
                        'quiz_completion' => $quizCompletion,
                    ];
                });
        }

        return Inertia::render('s1/mentor', [
            'modules' => $modules,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
            'waitingLists' => $waitingLists,
            'module2Users' => $module2Users,
        ]);
    }

    public function showModule2Progress(Request $request)
    {
        $module = S1Module::where('sequence_order', 2)->firstOrFail();

        $query = S1WaitingList::where('module_id', $module->id)
            ->where('is_active', true)
            ->with('user');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('vatsim_id', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('joined_at', 'asc')->paginate(20);

        $usersWithProgress = $users->map(function ($waitingList) use ($module) {
            $user = $waitingList->user;
            $progress = $this->getUserModule2DetailedProgress($user, $module);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'vatsim_id' => $user->vatsim_id,
                'joined_at' => $waitingList->joined_at->format('d.m.Y'),
                'progress' => $progress,
            ];
        });

        return Inertia::render('s1/mentor/module2-progress', [
            'users' => $usersWithProgress,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'search' => $request->search ?? '',
        ]);
    }

    protected function getUserModule2DetailedProgress($user, $module)
    {
        if (!$module->moodle_quiz_ids || !is_array($module->moodle_quiz_ids)) {
            return null;
        }

        $quizIds = $module->moodle_quiz_ids;
        $courseNames = [
            'Basics of controlling',
            'ATD Delivery',
            'ATD Ground',
            'ATD Tower',
        ];

        $quizzes = [];
        $completed = 0;
        $total = count($quizIds);

        foreach ($quizIds as $index => $quizId) {
            try {
                $isCompleted = $this->moodleService->getActivityCompletion(
                    $user->vatsim_id,
                    $quizId
                );

                if ($isCompleted) {
                    $completed++;
                }

                $quizzes[] = [
                    'id' => $quizId,
                    'name' => $courseNames[$index] ?? "Course " . ($index + 1),
                    'completed' => $isCompleted,
                ];
            } catch (\Exception $e) {
                $quizzes[] = [
                    'id' => $quizId,
                    'name' => $courseNames[$index] ?? "Course " . ($index + 1),
                    'completed' => false,
                    'error' => true,
                ];
            }
        }

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'quizzes' => $quizzes,
        ];
    }

    public function createSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:s1_modules,id',
            'scheduled_at' => 'required|date|after:now',
            'max_trainees' => 'required|integer|min:1|max:50',
            'language' => 'required|in:DE,EN',
            'notes' => 'nullable|string|max:1000',
        ]);

        $moduleSequence = S1Module::where('id', $validated['module_id'])->value('sequence_order');
        if ($moduleSequence == 2) {
            return redirect()->back()->with('error', 'Cannot create sessions for Module 2 as it uses Moodle courses only.');
        }

        $user = auth()->user();

        [$success, $message, $session] = $this->sessionService->createSession(
            $validated['module_id'],
            $user->id,
            \Carbon\Carbon::parse($validated['scheduled_at']),
            $validated['max_trainees'],
            $validated['language'],
            $validated['notes'] ?? null
        );

        if (!$success) {
            return redirect()->back()->with('error', $message);
        }

        return redirect()->back()->with('success', 'Session created successfully. Waiting list users have been notified.');
    }

    public function recordAttendance(Request $request, S1Session $session): RedirectResponse
    {
        if ($session->mentor_id !== auth()->id()) {
            return redirect()->back()->with('error', 'You are not authorized to record attendance for this session.');
        }

        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.signup_id' => 'required|exists:s1_session_signups,id',
            'attendances.*.status' => 'required|in:passed,failed,excused,absent',
            'attendances.*.remarks' => 'nullable|string|max:1000',
        ]);

        $selectedSignups = S1SessionSignup::where('session_id', $session->id)
            ->where('was_selected', true)
            ->pluck('id');

        $providedSignupIds = collect($validated['attendances'])->pluck('signup_id');

        if ($selectedSignups->count() !== $providedSignupIds->count()) {
            return redirect()->back()->with('error', 'Attendance must be recorded for all participants.');
        }

        $missingSignups = $selectedSignups->diff($providedSignupIds);
        if ($missingSignups->isNotEmpty()) {
            return redirect()->back()->with('error', 'Attendance must be recorded for all participants.');
        }

        try {
            foreach ($validated['attendances'] as $attendanceData) {
                $signup = S1SessionSignup::findOrFail($attendanceData['signup_id']);

                if ($signup->session_id !== $session->id) {
                    return redirect()->back()->with('error', 'Invalid signup for this session.');
                }

                [$success, $message] = $this->attendanceService->markAttendance(
                    $session,
                    $signup->user,
                    $attendanceData['status'],
                    $attendanceData['remarks'] ?? null,
                    auth()->id(),
                    false
                );

                if (!$success) {
                    return redirect()->back()->with('error', $message);
                }
            }

            $session->update(['attendance_completed' => true]);

            return redirect()->back()->with('success', 'Attendance recorded successfully for all participants.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to record attendance: ' . $e->getMessage());
        }
    }

    public function updateAttendance(Request $request, S1Attendance $attendance): RedirectResponse
    {
        $session = $attendance->session;

        if ($session->mentor_id !== auth()->id()) {
            return redirect()->back()->with('error', 'You are not authorized to update this attendance.');
        }

        $validated = $request->validate([
            'status' => 'required|in:passed,failed,excused,absent,attended',
            'remarks' => 'nullable|string|max:1000',
        ]);

        [$success, $message] = $this->attendanceService->markAttendance(
            $session,
            $attendance->user,
            $validated['status'],
            $validated['remarks'] ?? null,
            auth()->id(),
            $attendance->spontaneous ?? false
        );

        if (!$success) {
            return redirect()->back()->with('error', $message);
        }

        return redirect()->back()->with('success', 'Attendance updated successfully.');
    }

    public function deleteSession(Request $request, S1Session $session): RedirectResponse
    {
        if ($session->mentor_id !== auth()->id()) {
            return redirect()->back()->with('error', 'You are not authorized to delete this session.');
        }

        if ($session->attendance_completed) {
            return redirect()->back()->with('error', 'Cannot delete a session with completed attendance.');
        }

        if ($session->scheduled_at <= now()) {
            return redirect()->back()->with('error', 'Cannot delete a session that has already started.');
        }

        $session->delete();

        return redirect()->back()->with('success', 'Session deleted successfully.');
    }
}