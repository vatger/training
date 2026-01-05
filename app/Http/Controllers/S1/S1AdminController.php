<?php

namespace App\Http\Controllers\S1;

use App\Http\Controllers\Controller;
use App\Models\S1\S1UserBan;
use App\Models\S1\S1TraineeComment;
use App\Models\User;
use App\Services\S1\S1MentorStatsService;
use App\Services\S1\S1ProgressResetService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class S1AdminController extends Controller
{
    protected $mentorStatsService;
    protected $progressResetService;

    public function __construct(
        S1MentorStatsService $mentorStatsService,
        S1ProgressResetService $progressResetService
    ) {
        $this->mentorStatsService = $mentorStatsService;
        $this->progressResetService = $progressResetService;
    }

    public function banUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            $ban = S1UserBan::create([
                'user_id' => $user->id,
                'reason' => $validated['reason'],
                'banned_at' => now(),
                'expires_at' => $validated['expires_at'] ?? null,
                'banned_by_mentor_id' => $request->user()->id,
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'User banned successfully',
                'ban' => [
                    'id' => $ban->id,
                    'banned_at' => $ban->banned_at,
                    'expires_at' => $ban->expires_at,
                    'is_permanent' => $ban->isPermanent(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to ban user',
            ], 500);
        }
    }

    public function unbanUser(Request $request, User $user): JsonResponse
    {
        S1UserBan::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return response()->json([
            'message' => 'User unbanned successfully',
        ]);
    }

    public function listBans(Request $request): JsonResponse
    {
        $bans = S1UserBan::active()
            ->with(['user', 'bannedByMentor'])
            ->orderBy('banned_at', 'desc')
            ->paginate(50);

        return response()->json([
            'bans' => $bans->items(),
            'pagination' => [
                'total' => $bans->total(),
                'per_page' => $bans->perPage(),
                'current_page' => $bans->currentPage(),
                'last_page' => $bans->lastPage(),
            ],
        ]);
    }

    public function resetProgress(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string',
            'module_ids' => 'nullable|array',
            'module_ids.*' => 'exists:s1_modules,id',
        ]);

        [$success, $message, $reset] = $this->progressResetService->resetUserProgress(
            $user,
            $request->user()->id,
            $validated['reason'],
            $validated['module_ids'] ?? null
        );

        if (!$success) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        return response()->json([
            'message' => 'Progress reset successfully',
            'reset' => [
                'id' => $reset->id,
                'modules_reset' => $reset->modules_reset,
                'reset_at' => $reset->reset_at,
            ],
        ]);
    }

    public function addComment(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'required|string',
            'is_internal' => 'required|boolean',
        ]);

        $comment = S1TraineeComment::create([
            'user_id' => $user->id,
            'author_id' => $request->user()->id,
            'comment' => $validated['comment'],
            'is_internal' => $validated['is_internal'],
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'is_internal' => $comment->is_internal,
                'created_at' => $comment->created_at,
            ],
        ]);
    }

    public function getComments(Request $request, User $user): JsonResponse
    {
        $query = S1TraineeComment::where('user_id', $user->id)
            ->with('author');

        if (!$request->user()->hasRole('cos')) {
            $query->where('is_internal', false);
        }

        $comments = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'comments' => $comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'is_internal' => $comment->is_internal,
                    'author' => [
                        'id' => $comment->author->id,
                        'name' => $comment->author->name,
                    ],
                    'created_at' => $comment->created_at,
                ];
            }),
        ]);
    }

    public function mentorStats(Request $request, ?User $mentor = null): JsonResponse
    {
        $from = $request->from ? Carbon::parse($request->from) : null;
        $to = $request->to ? Carbon::parse($request->to) : null;

        if ($mentor) {
            $stats = $this->mentorStatsService->getMentorStats($mentor, $from, $to);
        } else {
            $stats = $this->mentorStatsService->getAllMentorsStats($from, $to);
        }

        return response()->json([
            'stats' => $stats,
        ]);
    }

    public function userProgress(Request $request, User $user): JsonResponse
    {
        $progress = $user->s1ModuleCompletions()
            ->with(['module', 'completedByMentor'])
            ->orderBy('completed_at', 'desc')
            ->get();

        return response()->json([
            'progress' => $progress->map(function ($completion) {
                return [
                    'module' => [
                        'id' => $completion->module->id,
                        'name' => $completion->module->name,
                        'sequence_order' => $completion->module->sequence_order,
                    ],
                    'completed_at' => $completion->completed_at,
                    'completed_by' => $completion->completedByMentor ? [
                        'id' => $completion->completedByMentor->id,
                        'name' => $completion->completedByMentor->name,
                    ] : null,
                    'was_reset' => $completion->was_reset,
                ];
            }),
        ]);
    }

    public function resetHistory(Request $request, User $user): JsonResponse
    {
        $resets = $this->progressResetService->getUserResetHistory($user);

        return response()->json([
            'resets' => $resets->map(function ($reset) {
                return [
                    'id' => $reset->id,
                    'reason' => $reset->reason,
                    'modules_reset' => $reset->modules_reset,
                    'reset_by' => [
                        'id' => $reset->resetByMentor->id,
                        'name' => $reset->resetByMentor->name,
                    ],
                    'reset_at' => $reset->reset_at,
                ];
            }),
        ]);
    }
}