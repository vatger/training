<?php

namespace App\Http\Controllers\S1;

use App\Http\Controllers\Controller;
use App\Models\S1\S1Module;
use App\Models\S1\S1Session;
use App\Services\S1\S1SessionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class S1SessionController extends Controller
{
    protected $sessionService;

    public function __construct(S1SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        /* if ($user->hasRole('cos')) {
            $sessions = $this->sessionService->getSessionsForCos($request->module_id);
        } else {
            $sessions = $this->sessionService->getUpcomingSessionsForUser($user);
        } */
        $sessions = $this->sessionService->getUpcomingSessionsForUser($user);

        return response()->json([
            'sessions' => $sessions->map(function ($session) {
                return $this->formatSession($session);
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:s1_modules,id',
            'mentor_id' => 'required|exists:users,id',
            'scheduled_at' => 'required|date|after:now',
            'max_trainees' => 'required|integer|min:1|max:50',
            'language' => 'required|in:DE,EN',
            'notes' => 'nullable|string',
        ]);

        [$success, $message, $session] = $this->sessionService->createSession(
            $validated['module_id'],
            $validated['mentor_id'],
            Carbon::parse($validated['scheduled_at']),
            $validated['max_trainees'],
            $validated['language'],
            $validated['notes'] ?? null
        );

        if (!$success) {
            return response()->json([
                'message' => $message,
            ], 500);
        }

        return response()->json([
            'message' => 'Session created successfully',
            'session' => $this->formatSession($session),
        ], 201);
    }

    public function show(Request $request, S1Session $session): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('cos') && !$user->hasRole('mentor')) {
            $hoursUntilSession = now()->diffInHours($session->scheduled_at, false);
            if ($hoursUntilSession > 48) {
                return response()->json([
                    'message' => 'Session details not yet available',
                ], 403);
            }
        }

        $session->load(['module', 'mentor', 'signups.user', 'attendances']);

        return response()->json([
            'session' => $this->formatSession($session, true),
        ]);
    }

    public function update(Request $request, S1Session $session): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => 'sometimes|date|after:now',
            'max_trainees' => 'sometimes|integer|min:1|max:50',
            'language' => 'sometimes|in:DE,EN',
            'notes' => 'nullable|string',
            'signups_open' => 'sometimes|boolean',
        ]);

        if (isset($validated['scheduled_at']) && $session->signups_locked) {
            return response()->json([
                'message' => 'Cannot change scheduled time after signups are locked',
            ], 422);
        }

        $session->update($validated);

        return response()->json([
            'message' => 'Session updated successfully',
            'session' => $this->formatSession($session),
        ]);
    }

    public function destroy(Request $request, S1Session $session): JsonResponse
    {
        if ($session->attendance_completed) {
            return response()->json([
                'message' => 'Cannot delete session with completed attendance',
            ], 422);
        }

        if ($session->signups()->exists()) {
            return response()->json([
                'message' => 'Cannot delete session with signups. Please cancel signups first.',
            ], 422);
        }

        $session->delete();

        return response()->json([
            'message' => 'Session deleted successfully',
        ]);
    }

    public function signup(Request $request, S1Session $session): JsonResponse
    {
        $user = $request->user();

        [$success, $message] = $this->sessionService->signupForSession($session, $user);

        if (!$success) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'message' => $message,
        ]);
    }

    public function cancelSignup(Request $request, S1Session $session): JsonResponse
    {
        $user = $request->user();

        [$success, $message] = $this->sessionService->cancelSignup($session, $user);

        if (!$success) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'message' => $message,
        ]);
    }

    public function lockSignups(Request $request, S1Session $session): JsonResponse
    {
        $success = $this->sessionService->lockSignups($session);

        if (!$success) {
            return response()->json([
                'message' => 'Failed to lock signups',
            ], 500);
        }

        return response()->json([
            'message' => 'Signups locked successfully',
        ]);
    }

    public function selectParticipants(Request $request, S1Session $session): JsonResponse
    {
        [$success, $message, $data] = $this->sessionService->selectParticipants($session);

        if (!$success) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'message' => $message,
            'data' => $data,
        ]);
    }

    protected function formatSession(S1Session $session, bool $includeDetails = false): array
    {
        $formatted = [
            'id' => $session->id,
            'module' => [
                'id' => $session->module->id,
                'name' => $session->module->name,
            ],
            'mentor' => [
                'id' => $session->mentor->id,
                'name' => $session->mentor->name,
            ],
            'scheduled_at' => $session->scheduled_at,
            'max_trainees' => $session->max_trainees,
            'language' => $session->language,
            'signups_open' => $session->signups_open,
            'signups_locked' => $session->signups_locked,
            'signups_lock_at' => $session->signups_lock_at,
            'attendance_completed' => $session->attendance_completed,
            'available_spots' => $session->available_spots,
            'total_signups' => $session->total_signups,
        ];

        if ($includeDetails) {
            $formatted['notes'] = $session->notes;
            $formatted['signups'] = $session->signups->map(function ($signup) {
                return [
                    'id' => $signup->id,
                    'user' => [
                        'id' => $signup->user->id,
                        'name' => $signup->user->name,
                    ],
                    'signed_up_at' => $signup->signed_up_at,
                    'was_selected' => $signup->was_selected,
                    'selected_at' => $signup->selected_at,
                ];
            });
        }

        return $formatted;
    }
}