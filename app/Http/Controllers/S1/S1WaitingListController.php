<?php

namespace App\Http\Controllers\S1;

use App\Http\Controllers\Controller;
use App\Models\S1\S1Module;
use App\Models\S1\S1WaitingList;
use App\Services\S1\S1WaitingListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class S1WaitingListController extends Controller
{
    protected $waitingListService;

    public function __construct(S1WaitingListService $waitingListService)
    {
        $this->waitingListService = $waitingListService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $waitingLists = S1WaitingList::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('module')
            ->orderBy('joined_at')
            ->get()
            ->map(function ($wl) {
                return [
                    'id' => $wl->id,
                    'module' => [
                        'id' => $wl->module->id,
                        'name' => $wl->module->name,
                        'sequence_order' => $wl->module->sequence_order,
                    ],
                    'joined_at' => $wl->joined_at,
                    'position' => $wl->position_in_queue,
                    'needs_confirmation' => $wl->needsConfirmation(),
                    'confirmation_due_at' => $wl->confirmation_due_at,
                    'expires_at' => $wl->expires_at,
                ];
            });

        return response()->json([
            'waiting_lists' => $waitingLists,
        ]);
    }

    public function join(Request $request, S1Module $module): JsonResponse
    {
        $request->validate([
            'confirm_requirements' => 'required|boolean|accepted',
        ]);

        $user = $request->user();

        [$canJoin, $reason] = $this->waitingListService->canJoinWaitingList($user, $module);

        if (!$canJoin) {
            return response()->json([
                'message' => $reason,
            ], 422);
        }

        [$success, $message, $waitingList] = $this->waitingListService->joinWaitingList($user, $module);

        if (!$success) {
            return response()->json([
                'message' => $message,
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully joined waiting list',
            'waiting_list' => [
                'id' => $waitingList->id,
                'module_id' => $waitingList->module_id,
                'joined_at' => $waitingList->joined_at,
                'position' => $waitingList->position_in_queue,
            ],
        ], 201);
    }

    public function leave(Request $request, S1Module $module): JsonResponse
    {
        $user = $request->user();

        [$success, $message] = $this->waitingListService->leaveWaitingList($user, $module);

        if (!$success) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'message' => $message,
        ]);
    }

    public function confirm(Request $request, S1WaitingList $waitingList): JsonResponse
    {
        if ($waitingList->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!$waitingList->is_active) {
            return response()->json([
                'message' => 'Waiting list entry is not active',
            ], 422);
        }

        [$success, $message, $updatedWaitingList] = $this->waitingListService->confirmWaitingList($waitingList);

        if (!$success) {
            return response()->json([
                'message' => $message,
            ], 500);
        }

        return response()->json([
            'message' => 'Waiting list confirmed successfully',
            'waiting_list' => [
                'id' => $updatedWaitingList->id,
                'last_confirmed_at' => $updatedWaitingList->last_confirmed_at,
                'confirmation_due_at' => $updatedWaitingList->confirmation_due_at,
                'expires_at' => $updatedWaitingList->expires_at,
            ],
        ]);
    }

    public function position(Request $request, S1Module $module): JsonResponse
    {
        $user = $request->user();

        $waitingList = S1WaitingList::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('is_active', true)
            ->first();

        if (!$waitingList) {
            return response()->json([
                'message' => 'Not on waiting list for this module',
            ], 404);
        }

        return response()->json([
            'position' => $waitingList->position_in_queue,
            'total_waiting' => S1WaitingList::where('module_id', $module->id)
                ->where('is_active', true)
                ->count(),
        ]);
    }
}