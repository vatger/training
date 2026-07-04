<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Integrations\VatEud\VatEudService;
use App\Models\Course;
use App\Models\User;
use App\Services\MentorCourseResponseBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class MentorOverviewController extends Controller
{
    public function __construct(
        private MentorCourseResponseBuilder $responseBuilder,
        private VatEudService $vatEudService,
    ) {}

    public function index(Request $request): Response
    {
        $user                = $request->user();
        $lastAccessedCourseId = $request->input('last_course_id');

        if ($user->is_superuser || $user->is_admin) {
            $courses = Course::select(['id', 'name', 'position', 'type', 'solo_station', 'mentor_group_id'])
                ->withCount('activeTrainees')
                ->get();
        } else {
            $accessibleCourseIds = $user->getAccessibleCourseIds();

            if (empty($accessibleCourseIds)) {
                return Inertia::render('training/mentor-overview', [
                    'courses'         => [],
                    'initialCourseId' => null,
                    'statistics'      => [
                        'activeTrainees' => 0,
                        'claimedTrainees' => 0,
                        'trainingSessions' => 0,
                        'waitingList' => 0,
                    ],
                ]);
            }

            $courses = Course::select(['id', 'name', 'position', 'type', 'solo_station', 'mentor_group_id'])
                ->whereIn('id', $accessibleCourseIds)
                ->withCount('activeTrainees')
                ->get();
        }

        $positionOrder = ['GND' => 1, 'TWR' => 2, 'APP' => 3];

        $nonCtr = $courses->filter(fn($c) => $c->position !== 'CTR')->sortBy(fn($c) => $positionOrder[$c->position] ?? 999)->sortBy('name');
        $ctr     = $courses->filter(fn($c) => $c->position === 'CTR')->sortBy('name');
        $courses = $nonCtr->concat($ctr)->values();

        $courseIds = $courses->pluck('id')->toArray();

        $cotCourseIds = DB::table('chief_of_trainings')
            ->where('user_id', $user->id)
            ->whereIn('course_id', $courseIds)
            ->pluck('course_id')
            ->toArray();

        $lmFirs = DB::table('leading_mentors')
            ->where('user_id', $user->id)
            ->pluck('fir')
            ->toArray();

        $mentorGroups = DB::table('roles')
            ->whereIn('id', $courses->pluck('mentor_group_id')->filter()->unique())
            ->pluck('name', 'id')
            ->toArray();

        $coursesMetadata = $courses->map(function ($course) use ($user, $cotCourseIds, $lmFirs, $mentorGroups) {
            $isCoT = in_array($course->id, $cotCourseIds);
            $isLM  = false;

            if ($course->mentor_group_id && isset($mentorGroups[$course->mentor_group_id])) {
                $fir = $user->getFirFromMentorGroup($mentorGroups[$course->mentor_group_id]);
                if ($fir && in_array($fir, $lmFirs)) {
                    $isLM = true;
                }
            }

            return [
                'id'             => $course->id,
                'name'           => $course->name,
                'position'       => $course->position,
                'type'           => $course->type,
                'soloStation'    => $course->solo_station,
                'activeTrainees' => $course->active_trainees_count,
                'trainees'       => [],
                'loaded'         => false,
                'permissions'    => [
                    'isChiefOfTraining' => $isCoT,
                    'isLeadingMentor' => $isLM,
                    'canEditAllLogs' => $isCoT || $isLM || $user->is_superuser || $user->is_admin,
                    'canRemoveEndorsements' => $isCoT || $isLM || $user->is_superuser || $user->is_admin,
                ],
            ];
        });

        $courseToLoadId = null;

        if ($lastAccessedCourseId && $courses->contains('id', $lastAccessedCourseId)) {
            $courseToLoadId = $lastAccessedCourseId;
        } elseif ($courses->isNotEmpty()) {
            $courseToLoadId = $courses->first()->id;
        }

        if ($courseToLoadId) {
            try {
                $courseToLoad = Course::find($courseToLoadId);
                if ($courseToLoad) {
                    $loadedCourseData = $this->responseBuilder->build($courseToLoad, $user, $this->buildEndorsementsMap($courseToLoad));
                    $loadedCourseData['loaded'] = true;

                    $coursesMetadata = $coursesMetadata->map(function ($meta) use ($loadedCourseData) {
                        if ($meta['id'] === $loadedCourseData['id']) {
                            $loadedCourseData['permissions'] = $meta['permissions'];
                            return $loadedCourseData;
                        }
                        return $meta;
                    });
                }
            } catch (\Exception $e) {
                Log::error('Failed to load initial course data', [
                    'course_id' => $courseToLoadId,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        $accessibleCourseIds = $courses->pluck('id')->toArray();

        return Inertia::render('training/mentor-overview', [
            'courses'         => $coursesMetadata->values(),
            'initialCourseId' => $courseToLoadId,
            'statistics'      => [
                'activeTrainees' => $courses->sum(fn($c) => $c->active_trainees_count),
                'claimedTrainees' => DB::table('course_trainees')
                    ->whereIn('course_id', $accessibleCourseIds)
                    ->where('claimed_by_mentor_id', $user->id)
                    ->whereNull('completed_at')
                    ->count(),
                'trainingSessions' => DB::table('training_logs')
                    ->whereIn('course_id', $accessibleCourseIds)
                    ->where('mentor_id', $user->id)
                    ->where('session_date', '>=', now()->subDays(30))
                    ->count(),
                'waitingList' => DB::table('waiting_list_entries')
                    ->whereIn('course_id', $accessibleCourseIds)
                    ->count(),
            ],
        ]);
    }

    public function loadCourseTrainees(Request $request, $courseId)
    {
        $user = $request->user();
        $course = Course::findOrFail($courseId);

        if (!$user->isMentor() && !$user->is_superuser && !$user->isChiefOfTraining() && !$user->isLeadingMentor()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$user->canViewCourse($course)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json($this->responseBuilder->build($course, $user, $this->buildEndorsementsMap($course)));
    }

    public function getMoodleStatusForTrainee(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $trainee = \App\Models\User::findOrFail($request->input('trainee_id'));
        $course  = Course::findOrFail($request->input('course_id'));

        if (empty($course->moodle_course_ids)) {
            return response()->json([
                'success' => true,
                'status'  => null,
                'message' => 'Course does not require Moodle completion',
            ]);
        }

        $cacheKey = "moodle_status_{$trainee->vatsim_id}_{$course->id}";
        $cached   = Cache::get($cacheKey);

        if ($cached !== null) {
            return response()->json(['success' => true, 'status' => $cached, 'cached' => true]);
        }

        try {
            $moodleClient = app(\App\Integrations\Moodle\MoodleClient::class);

            $status = $moodleClient->userExists($trainee->vatsim_id)
                ? (
                    collect($course->moodle_course_ids)->every(
                        fn($courseId) => $moodleClient->getCourseCompletion($trainee->vatsim_id, $courseId)
                    )
                    ? 'completed'
                    : 'in-progress'
                )
                : 'not-started';

            Cache::put($cacheKey, $status, 300);

            return response()->json(['success' => true, 'status' => $status, 'cached' => false]);
        } catch (\Exception $e) {
            Log::error('Moodle status check failed', [
                'trainee_id' => $trainee->id,
                'vatsim_id' => $trainee->vatsim_id,
                'course_id' => $course->id,
                'moodle_course_ids' => $course->moodle_course_ids,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'status'  => 'unknown',
                'error'   => 'Failed to check Moodle status',
            ]);
        }
    }

    public function getPastTrainees(Request $request, $courseId)
    {
        $user = $request->user();
        $course = Course::findOrFail($courseId);

        if (!$user->isMentor() && !$user->isSuperuser()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if (!$user->canViewCourse($course)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        try {
            $pastTrainees = DB::table('course_trainees')
                ->join('users', 'course_trainees.user_id', '=', 'users.id')
                ->where('course_trainees.course_id', $courseId)
                ->whereNotNull('course_trainees.completed_at')
                ->select('users.id', 'users.vatsim_id', 'users.first_name', 'users.last_name', 'course_trainees.completed_at')
                ->orderBy('course_trainees.completed_at', 'desc')
                ->get()
                ->map(fn($t) => [
                    'id'           => $t->id,
                    'vatsim_id'    => $t->vatsim_id,
                    'name'         => $t->first_name . ' ' . $t->last_name,
                    'completed_at' => \Carbon\Carbon::parse($t->completed_at)->format('Y-m-d'),
                ]);

            return response()->json(['success' => true, 'trainees' => $pastTrainees]);
        } catch (\Exception $e) {
            Log::error('Error fetching past trainees', ['course_id' => $courseId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch past trainees'], 500);
        }
    }

    public function getCourseMentors(Request $request, $courseId)
    {
        $user = $request->user();
        $course = Course::findOrFail($courseId);

        if (!$user->isMentor() && !$user->isSuperuser()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if (!$user->canViewCourse($course)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json(
            $course->mentors()->get()->map(fn($mentor) => [
                'id' => $mentor->id,
                'name' => $mentor->name,
                'vatsim_id' => $mentor->vatsim_id,
            ])
        );
    }

    public function getTraineeLogs(Request $request, int $traineeId)
    {
        $user = $request->user();

        User::findOrFail($traineeId);

        $query = \App\Models\TrainingLog::with(['mentor', 'course'])
            ->where('trainee_id', $traineeId)
            ->orderBy('session_date', 'desc');

        if (!$user->is_superuser && !$user->is_admin) {
            $accessibleCourseIds = $user->getAccessibleCourseIds();
            $query->whereIn('course_id', $accessibleCourseIds);
        }

        if ($courseId = $request->query('course_id')) {
            $query->where('course_id', (int) $courseId);
        }

        $typeDisplayMap = ['O' => 'Online', 'S' => 'Sim', 'L' => 'Lesson', 'C' => 'Custom'];

        $logs = $query->get()->map(fn($log) => [
            'id'               => $log->id,
            'session_date'     => $log->session_date->format('Y-m-d'),
            'position'         => $log->position,
            'type'             => $log->type,
            'type_display'     => $typeDisplayMap[$log->type] ?? $log->type,
            'result'           => (bool) $log->result,
            'average_rating'   => $log->average_rating,
            'session_duration' => $log->session_duration,
            'final_comment'    => $log->final_comment,
            'next_step'        => $log->next_step,
            'mentor'           => $log->mentor ? [
                'id'   => $log->mentor->id,
                'name' => $log->mentor->name,
            ] : null,
            'course' => $log->course ? [
                'id'       => $log->course->id,
                'name'     => $log->course->name,
                'position' => $log->course->position,
                'type'     => $log->course->type,
            ] : null,
        ]);

        return response()->json(['logs' => $logs]);
    }

    public function grantEndorsement(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $course  = Course::findOrFail($validated['course_id']);
        $trainee = User::findOrFail($validated['trainee_id']);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'Access denied to this course']);
        }

        $endorsementGroups = $course->endorsementGroups();

        if ($endorsementGroups->isEmpty()) {
            return back()->withErrors(['error' => 'This course has no endorsements configured']);
        }

        if (!$trainee->isVatsimUser()) {
            return back()->withErrors(['error' => 'Trainee does not have a VATSIM account']);
        }

        $failed = [];
        foreach ($endorsementGroups as $position) {
            try {
                $granted = $this->vatEudService->createTier1Endorsement(
                    $trainee->vatsim_id,
                    $position,
                    $user->vatsim_id,
                );

                if (!$granted) {
                    $failed[] = $position;
                }
            } catch (\Exception $e) {
                Log::error('Failed to grant tier1 endorsement', [
                    'mentor_id'  => $user->id,
                    'trainee_id' => $trainee->id,
                    'course_id'  => $course->id,
                    'position'   => $position,
                    'error'      => $e->getMessage(),
                ]);
                $failed[] = $position;
            }
        }

        if (!empty($failed)) {
            return back()->withErrors(['error' => 'Failed to grant endorsements for: ' . implode(', ', $failed)]);
        }

        return redirect()->route('overview.index', ['last_course_id' => $course->id]);
    }

    private function buildEndorsementsMap(Course $course): array
    {
        $endorsementGroupPositions = $course->endorsementGroups()->toArray();
        $hasSoloStation = !empty($course->solo_station);

        if (!$hasSoloStation && empty($endorsementGroupPositions)) {
            return [];
        }

        try {
            $allTier1 = $this->vatEudService->getTier1Endorsements();

            $solos = $hasSoloStation
                ? collect($this->vatEudService->getSoloEndorsements())
                    ->where('position', $course->solo_station)
                    ->keyBy('userCid')
                : collect();

            $tier1 = !empty($endorsementGroupPositions)
                ? collect($allTier1)
                    ->filter(fn($e) => in_array($e->position, $endorsementGroupPositions))
                    ->keyBy('userCid')
                : collect();
        } catch (\Exception $e) {
            Log::error('buildEndorsementsMap exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return [];
        }

        $now = Carbon::now();
        $map = [];

        foreach ($solos as $cid => $solo) {
            $expiry = Carbon::parse($solo->expireAt);
            $map[$cid]['soloStatus'] = [
                'remaining' => max(0, (int) $now->diffInDays($expiry, false)),
                'used' => (int) Carbon::parse($solo->createdAt)->diffInDays($now),
                'extensionDaysLeft' => 31,
                'expiry' => $expiry->format('Y-m-d'),
            ];
        }

        foreach ($tier1 as $cid => $endorsement) {
            $map[$cid]['endorsementStatus'] = $endorsement->position;
        }

        return $map;
    }
}