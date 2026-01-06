<?php

namespace App\Http\Controllers\S1;

use App\Http\Controllers\Controller;
use App\Models\S1\S1Module;
use App\Models\S1\S1Session;
use App\Models\S1\S1Attendance;
use App\Models\S1\S1SessionSignup;
use App\Models\S1\S1WaitingList;
use App\Services\S1\S1AttendanceService;
use App\Services\S1\S1SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class S1MentorController extends Controller
{
    protected $sessionService;
    protected $attendanceService;

    public function __construct(
        S1SessionService $sessionService,
        S1AttendanceService $attendanceService
    ) {
        $this->sessionService = $sessionService;
        $this->attendanceService = $attendanceService;
    }

    public function index(): Response
    {
        $user = auth()->user();

        $modules = S1Module::orderBy('sequence_order')->get();

        $upcomingSessions = S1Session::with(['module', 'mentor', 'signups.user', 'signups.waitingList'])
            ->where('scheduled_at', '>', now())
            ->where('mentor_id', $user->id)
            ->orderBy('scheduled_at')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'module_name' => $session->module->name,
                    'module_id' => $session->module_id,
                    'scheduled_at' => $session->scheduled_at,
                    'max_trainees' => $session->max_trainees,
                    'language' => $session->language,
                    'signups_open' => $session->signups_open,
                    'signups_locked' => $session->signups_locked,
                    'attendance_completed' => $session->attendance_completed,
                    'total_signups' => $session->signups->count(),
                    'selected_count' => $session->signups->where('was_selected', true)->count(),
                    'participants' => $session->signups()
                        ->where('was_selected', true)
                        ->with(['user'])
                        ->get()
                        ->map(function ($signup) {
                            // Query attendance using session_id and user_id
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
                                    'remarks' => $attendance->notes,
                                ] : null,
                            ];
                        }),
                ];
            });

        $pastSessions = S1Session::with(['module', 'signups'])
            ->where('mentor_id', $user->id)
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'module_name' => $session->module->name,
                    'scheduled_at' => $session->scheduled_at,
                    'max_trainees' => $session->max_trainees,
                    'attendance_completed' => $session->attendance_completed,
                    'participants_count' => $session->signups()->where('was_selected', true)->count(),
                ];
            });

        $waitingLists = S1Module::with(['waitingLists' => function ($query) {
            $query->where('is_active', true)
                ->orderBy('joined_at')
                ->with('user');
        }])
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
                            'joined_at' => $wl->joined_at,
                            'last_confirmed_at' => $wl->last_confirmed_at,
                            'needs_confirmation' => $wl->confirmation_due_at?->isPast(),
                        ];
                    }),
                ];
            });

        return Inertia::render('s1/mentor', [
            'modules' => $modules,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
            'waitingLists' => $waitingLists,
        ]);
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

        try {
            foreach ($validated['attendances'] as $attendanceData) {
                $signup = S1SessionSignup::findOrFail($attendanceData['signup_id']);

                if ($signup->session_id !== $session->id) {
                    return redirect()->back()->with('error', 'Invalid signup for this session.');
                }

                // Use markAttendance from the service
                [$success, $message] = $this->attendanceService->markAttendance(
                    $session,
                    $signup->user,
                    $attendanceData['status'],
                    $attendanceData['remarks'] ?? null,
                    auth()->id(),
                    false // not spontaneous
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

        // Use markAttendance which handles updateOrCreate
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

    public function toggleSessionSignups(Request $request, S1Session $session): RedirectResponse
    {
        if ($session->mentor_id !== auth()->id()) {
            return redirect()->back()->with('error', 'You are not authorized to modify this session.');
        }

        $session->update(['signups_open' => !$session->signups_open]);

        $status = $session->signups_open ? 'opened' : 'closed';
        return redirect()->back()->with('success', "Session signups have been {$status}.");
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