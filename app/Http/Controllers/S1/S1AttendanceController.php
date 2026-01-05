<?php

namespace App\Http\Controllers\S1;

use App\Http\Controllers\Controller;
use App\Models\S1\S1Session;
use App\Models\User;
use App\Services\S1\S1AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class S1AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(S1AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request, S1Session $session): JsonResponse
    {
        $attendances = $this->attendanceService->getSessionAttendances($session);

        return response()->json([
            'attendances' => $attendances->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'user' => [
                        'id' => $attendance->user->id,
                        'name' => $attendance->user->name,
                    ],
                    'status' => $attendance->status,
                    'notes' => $attendance->notes,
                    'marked_by' => $attendance->markedByMentor ? [
                        'id' => $attendance->markedByMentor->id,
                        'name' => $attendance->markedByMentor->name,
                    ] : null,
                    'marked_at' => $attendance->marked_at,
                    'spontaneous' => $attendance->spontaneous,
                ];
            }),
        ]);
    }

    public function mark(Request $request, S1Session $session, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:attended,absent,excused,passed,failed',
            'notes' => 'nullable|string',
        ]);

        [$success, $message, $attendance] = $this->attendanceService->markAttendance(
            $session,
            $user,
            $validated['status'],
            $validated['notes'] ?? null,
            $request->user()->id,
            false
        );

        if (!$success) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'message' => 'Attendance marked successfully',
            'attendance' => [
                'id' => $attendance->id,
                'status' => $attendance->status,
                'marked_at' => $attendance->marked_at,
            ],
        ]);
    }

    public function markBulk(Request $request, S1Session $session): JsonResponse
    {
        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.user_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:attended,absent,excused,passed,failed',
            'attendances.*.notes' => 'nullable|string',
            'attendances.*.spontaneous' => 'sometimes|boolean',
        ]);

        [$success, $message, $data] = $this->attendanceService->markAllAttendance(
            $session,
            $validated['attendances'],
            $request->user()->id
        );

        if (!$success) {
            return response()->json([
                'message' => $message,
            ], 500);
        }

        return response()->json([
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function addSpontaneous(Request $request, S1Session $session): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:attended,passed,failed',
            'notes' => 'nullable|string',
        ]);

        $user = User::findOrFail($validated['user_id']);

        [$success, $message, $attendance] = $this->attendanceService->addSpontaneousAttendee(
            $session,
            $user,
            $validated['status'],
            $validated['notes'] ?? null,
            $request->user()->id
        );

        if (!$success) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'message' => 'Spontaneous attendee added successfully',
            'attendance' => [
                'id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'status' => $attendance->status,
            ],
        ]);
    }

    public function userHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        $attendances = $this->attendanceService->getUserAttendanceHistory($user);

        return response()->json([
            'attendances' => $attendances->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'session' => [
                        'id' => $attendance->session->id,
                        'module_name' => $attendance->session->module->name,
                        'scheduled_at' => $attendance->session->scheduled_at,
                    ],
                    'status' => $attendance->status,
                    'notes' => $attendance->notes,
                    'marked_at' => $attendance->marked_at,
                    'spontaneous' => $attendance->spontaneous,
                ];
            }),
        ]);
    }
}