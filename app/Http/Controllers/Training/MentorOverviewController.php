<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\ActivityLogger;
use App\Services\MentorCourseResponseBuilder;
use App\Models\Course;

class MentorOverviewController extends Controller
{
    public function __construct(
        private MentorCourseResponseBuilder $responseBuilder,
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
                        'activeTrainees'  => 0,
                        'claimedTrainees' => 0,
                        'trainingSessions' => 0,
                        'waitingList'     => 0,
                    ],
                ]);
            }

            $courses = Course::select(['id', 'name', 'position', 'type', 'solo_station', 'mentor_group_id'])
                ->whereIn('id', $accessibleCourseIds)
                ->withCount('activeTrainees')
                ->get();
        }

        $positionOrder = ['GND' => 1, 'TWR' => 2, 'APP' => 3];

        $nonCtr = $courses->filter(fn($c) => $c->position !== 'CTR')
            ->sortBy(fn($c) => $positionOrder[$c->position] ?? 999)
            ->sortBy('name');

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
                    'isChiefOfTraining'    => $isCoT,
                    'isLeadingMentor'      => $isLM,
                    'canEditAllLogs'       => $isCoT || $isLM || $user->is_superuser || $user->is_admin,
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
                    $loadedCourseData           = $this->loadCourseData($courseToLoad, $user);
                    $loadedCourseData['loaded']  = true;

                    $coursesMetadata = $coursesMetadata->map(function ($meta) use ($loadedCourseData) {
                        if ($meta['id'] === $loadedCourseData['id']) {
                            $loadedCourseData['permissions'] = $meta['permissions'];
                            return $loadedCourseData;
                        }
                        return $meta;
                    });
                }
            } catch (\Exception $e) {
                \Log::error('Failed to load initial course data', [
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
                'activeTrainees'  => $courses->sum(fn($c) => $c->active_trainees_count),
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

        if (!$user->isMentor() && !$user->is_superuser && !$user->isChiefOfTraining() && !$user->isLeadingMentor()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $course = Course::findOrFail($courseId);

        if (!$user->canViewCourse($course)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json($this->loadCourseData($course, $user));
    }

    public function loadCourseData($course, $user): array
    {
        $allSolos = Cache::remember('vateud_solos', 300, function () {
            try {
                return collect(app(\App\Services\VatEudService::class)->getSoloEndorsements());
            } catch (\Exception $e) {
                \Log::error('VatEUD solos: ' . $e->getMessage());
                return collect();
            }
        });

        $allTier1 = Cache::remember('vateud_tier1', 300, function () {
            try {
                return collect(app(\App\Services\VatEudService::class)->getTier1Endorsements());
            } catch (\Exception $e) {
                \Log::error('VatEUD tier1: ' . $e->getMessage());
                return collect();
            }
        });

        $solosByVatsimId  = $allSolos->groupBy('user_cid');
        $tier1ByVatsimId  = $allTier1->groupBy('user_cid');

        $courseWithTrainees = Course::with([
            'activeTrainees' => function ($query) {
                $query->orderByRaw("
                    CASE
                        WHEN course_trainees.custom_order IS NOT NULL
                        THEN course_trainees.custom_order
                        ELSE 999999
                    END ASC,
                    users.first_name ASC,
                    users.last_name ASC
                ");
            },
        ])->find($course->id);

        $traineeIds = $courseWithTrainees->activeTrainees->pluck('id');

        $allTrainingLogs = collect();
        if ($traineeIds->isNotEmpty()) {
            $allTrainingLogs = \App\Models\TrainingLog::select([
                'id', 'trainee_id', 'course_id', 'session_date', 'result', 'next_step',
            ])
                ->whereIn('trainee_id', $traineeIds)
                ->where('course_id', $course->id)
                ->orderBy('trainee_id')
                ->orderBy('session_date', 'desc')
                ->get()
                ->groupBy('trainee_id')
                ->mapWithKeys(fn($logs, $traineeId) => [$traineeId . '_' . $course->id => $logs]);
        }

        $pivotData = collect();
        if ($traineeIds->isNotEmpty()) {
            $pivotData = DB::table('course_trainees')
                ->leftJoin('users as remark_author', 'course_trainees.remark_author_id', '=', 'remark_author.id')
                ->leftJoin('users as claimed_mentor', 'course_trainees.claimed_by_mentor_id', '=', 'claimed_mentor.id')
                ->where('course_trainees.course_id', $course->id)
                ->whereIn('course_trainees.user_id', $traineeIds)
                ->select(
                    'course_trainees.course_id',
                    'course_trainees.user_id',
                    'course_trainees.remarks',
                    'course_trainees.remark_updated_at',
                    'course_trainees.claimed_by_mentor_id',
                    'remark_author.first_name as author_first_name',
                    'remark_author.last_name as author_last_name',
                    'claimed_mentor.id as claimed_mentor_id',
                    'claimed_mentor.first_name as claimed_first_name',
                    'claimed_mentor.last_name as claimed_last_name',
                )
                ->get()
                ->keyBy(fn($item) => $item->course_id . '_' . $item->user_id);
        }

        $trainees = $courseWithTrainees->activeTrainees->map(function ($trainee) use ($courseWithTrainees, $user, $solosByVatsimId, $tier1ByVatsimId, $allTrainingLogs, $pivotData) {
            return $this->formatTraineeOptimized($trainee, $courseWithTrainees, $user, $solosByVatsimId, $tier1ByVatsimId, $allTrainingLogs, $pivotData);
        });

        $isCoT = DB::table('chief_of_trainings')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        $isLM = false;
        if ($courseWithTrainees->mentor_group_id) {
            $mentorGroupName = DB::table('roles')
                ->where('id', $courseWithTrainees->mentor_group_id)
                ->value('name');

            if ($mentorGroupName) {
                $fir = $user->getFirFromMentorGroup($mentorGroupName);
                if ($fir) {
                    $isLM = DB::table('leading_mentors')
                        ->where('user_id', $user->id)
                        ->where('fir', $fir)
                        ->exists();
                }
            }
        }

        return [
            'id'             => $courseWithTrainees->id,
            'name'           => $courseWithTrainees->name,
            'position'       => $courseWithTrainees->position,
            'type'           => $courseWithTrainees->type,
            'soloStation'    => $courseWithTrainees->solo_station,
            'moodleCourseIds' => $courseWithTrainees->moodle_course_ids ?? [],
            'activeTrainees' => $courseWithTrainees->activeTrainees->count(),
            'trainees'       => $trainees,
            'loaded'         => true,
            'permissions'    => [
                'isChiefOfTraining'    => $isCoT,
                'isLeadingMentor'      => $isLM,
                'canEditAllLogs'       => $isCoT || $isLM || $user->is_superuser || $user->is_admin,
                'canRemoveEndorsements' => $isCoT || $isLM || $user->is_superuser || $user->is_admin,
            ],
        ];
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
            $moodleService = app(\App\Services\MoodleService::class);

            $status = $moodleService->userExists($trainee->vatsim_id)
                ? ($moodleService->checkAllCoursesCompleted($trainee->vatsim_id, $course->moodle_course_ids) ? 'completed' : 'in-progress')
                : 'not-started';

            Cache::put($cacheKey, $status, 300);

            return response()->json(['success' => true, 'status' => $status, 'cached' => false]);
        } catch (\Exception $e) {
            \Log::error('Moodle status check failed', [
                'trainee_id'       => $trainee->id,
                'vatsim_id'        => $trainee->vatsim_id,
                'course_id'        => $course->id,
                'moodle_course_ids' => $course->moodle_course_ids,
                'error'            => $e->getMessage(),
                'trace'            => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'status'  => 'unknown',
                'error'   => 'Failed to check Moodle status',
            ]);
        }
    }

    protected function formatTraineeOptimized($trainee, $course, $currentMentor, $solosByVatsimId, $tier1ByVatsimId, $allTrainingLogs, $pivotData): array
    {
        $traineeVatsimId  = $trainee->vatsim_id;
        $soloEndorsements = $solosByVatsimId->get($traineeVatsimId, collect());

        $solo = $soloEndorsements->first(function ($s) use ($course) {
            $soloPos    = explode('_', $s['position']);
            $coursePos  = $course->position;
            return $soloPos[0] === $course->airport_icao &&
                (end($soloPos) === $coursePos || ($coursePos === 'GND' && end($soloPos) === 'GNDDEL'));
        });

        $soloStatus = null;
        if ($solo) {
            $expiryDate     = \Carbon\Carbon::parse($solo['expiry']);
            $daysRemaining  = max(0, ceil(\Carbon\Carbon::now()->diffInHours($expiryDate, false) / 24));

            $soloStatus = [
                'remaining'        => (int) $daysRemaining,
                'used'             => (int) ($solo['position_days'] ?? 0),
                'extensionDaysLeft' => 90 - (int) ($solo['position_days'] ?? 0),
                'expiry'           => $expiryDate->format('Y-m-d'),
            ];
        }

        $endorsementStatus = null;
        if (in_array($course->type, ['GST', 'EDMT']) || ($course->type === 'RTG' && $course->position === 'GND')) {
            $traineeEndorsements = $tier1ByVatsimId->get($traineeVatsimId, collect());
            $endorsementGroups   = $course->endorsementGroups()->toArray();

            foreach ($endorsementGroups as $groupName) {
                if ($traineeEndorsements->first(fn($e) => $e['position'] === $groupName)) {
                    $endorsementStatus = $groupName;
                    break;
                }
            }

            if (!$endorsementStatus && !empty($course->solo_station)) {
                $endorsement = $traineeEndorsements->first(fn($e) => $e['position'] === $course->solo_station);
                if ($endorsement) {
                    $endorsementStatus = $course->solo_station;
                }
            }
        }

        $logKey              = $trainee->id . '_' . $course->id;
        $traineeLogsForCourse = $allTrainingLogs->get($logKey, collect());

        $progress    = $traineeLogsForCourse->map(fn($log) => $log->result ?? false)->reverse()->values()->toArray();
        $lastSession = $traineeLogsForCourse->isNotEmpty()
            ? $traineeLogsForCourse->first()->session_date->toIso8601String()
            : null;
        $nextStep    = $traineeLogsForCourse->isNotEmpty() && $traineeLogsForCourse->first()->next_step
            ? $traineeLogsForCourse->first()->next_step
            : '';

        $pivotKey = $course->id . '_' . $trainee->id;
        $pivot    = $pivotData->get($pivotKey);

        $claimedBy        = null;
        $claimedByMentorId = $pivot?->claimed_by_mentor_id;

        if ($claimedByMentorId && $pivot->claimed_mentor_id) {
            $claimedBy = $pivot->claimed_mentor_id === $currentMentor->id
                ? 'You'
                : $pivot->claimed_first_name . ' ' . $pivot->claimed_last_name;
        }

        $remarkData = null;
        if ($pivot && !empty($pivot->remarks)) {
            $remarkData = [
                'text'           => $pivot->remarks,
                'updated_at'     => $pivot->remark_updated_at
                    ? \Carbon\Carbon::parse($pivot->remark_updated_at)->toIso8601String()
                    : null,
                'author_initials' => ($pivot->author_first_name && $pivot->author_last_name)
                    ? strtoupper(mb_substr($pivot->author_first_name, 0, 1) . mb_substr($pivot->author_last_name, 0, 1))
                    : null,
                'author_name'    => ($pivot->author_first_name && $pivot->author_last_name)
                    ? $pivot->author_first_name . ' ' . $pivot->author_last_name
                    : null,
            ];
        }

        return [
            'id'               => $trainee->id,
            'name'             => $trainee->name,
            'vatsimId'         => $trainee->vatsim_id,
            'initials'         => $this->getInitials($trainee->first_name, $trainee->last_name),
            'progress'         => $progress,
            'lastSession'      => $lastSession,
            'nextStep'         => $nextStep,
            'claimedBy'        => $claimedBy,
            'claimedByMentorId' => $claimedByMentorId,
            'soloStatus'       => $soloStatus,
            'moodleStatus'     => null,
            'endorsementStatus' => $endorsementStatus,
            'remark'           => $remarkData,
        ];
    }

    protected function getInitials(string $firstName, string $lastName): string
    {
        return strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1));
    }

    public function getCourseMentors(Request $request, $courseId)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $course = Course::findOrFail($courseId);

        if (!$user->canViewCourse($course)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json(
            $course->mentors()->get()->map(fn($mentor) => [
                'id'        => $mentor->id,
                'name'      => $mentor->name,
                'vatsim_id' => $mentor->vatsim_id,
            ])
        );
    }

    public function updateRemark(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
            'remark'     => 'nullable|string|max:1000',
        ]);

        $course  = Course::findOrFail($request->course_id);
        $trainee = \App\Models\User::findOrFail($request->trainee_id);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        try {
            DB::table('course_trainees')
                ->where('course_id', $request->course_id)
                ->where('user_id', $request->trainee_id)
                ->update([
                    'remarks'          => $request->remark ?? '',
                    'remark_author_id' => $user->id,
                    'remark_updated_at' => now(),
                ]);

            ActivityLogger::remarksUpdated($course, $trainee, $user, $request->remark ?? '');

            return $this->responseBuilder->build($course, $user);
        } catch (\Exception $e) {
            \Log::error('Error updating trainee remark', [
                'mentor_id'  => $user->id,
                'trainee_id' => $request->trainee_id,
                'course_id'  => $request->course_id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while updating the remark.']);
        }
    }

    public function removeTrainee(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $course  = Course::findOrFail($request->course_id);
        $trainee = \App\Models\User::findOrFail($request->trainee_id);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        try {
            DB::table('course_trainees')
                ->where('course_id', $course->id)
                ->where('user_id', $trainee->id)
                ->update([
                    'completed_at'        => now(),
                    'status'              => 'removed',
                    'claimed_by_mentor_id' => null,
                    'claimed_at'          => null,
                ]);

            ActivityLogger::traineeRemoved($course, $trainee, $user);

            return $this->responseBuilder->build($course, $user);
        } catch (\Exception $e) {
            \Log::error('Error removing trainee from course', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while removing the trainee.']);
        }
    }

    public function claimTrainee(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $course  = Course::findOrFail($request->course_id);
        $trainee = \App\Models\User::findOrFail($request->trainee_id);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot claim trainees for this course']);
        }

        if (!$course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
            return back()->withErrors(['error' => 'Trainee is not in this course']);
        }

        try {
            DB::table('course_trainees')
                ->where('course_id', $course->id)
                ->where('user_id', $trainee->id)
                ->update([
                    'claimed_by_mentor_id' => $user->id,
                    'claimed_at'           => now(),
                ]);

            ActivityLogger::traineeClaimed($course, $trainee, $user);

            return $this->responseBuilder->build($course, $user);
        } catch (\Exception $e) {
            \Log::error('Error claiming trainee', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while claiming the trainee.']);
        }
    }

    public function assignTrainee(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
            'mentor_id'  => 'required|integer|exists:users,id',
        ]);

        $course    = Course::findOrFail($request->course_id);
        $trainee   = \App\Models\User::findOrFail($request->trainee_id);
        $newMentor = \App\Models\User::findOrFail($request->mentor_id);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot assign trainees for this course']);
        }

        if (!$newMentor->is_superuser && !$newMentor->is_admin && !$newMentor->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return back()->withErrors(['error' => 'Selected mentor cannot mentor this course']);
        }

        if (!$course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
            return back()->withErrors(['error' => 'Trainee is not in this course']);
        }

        try {
            DB::table('course_trainees')
                ->where('course_id', $course->id)
                ->where('user_id', $trainee->id)
                ->update([
                    'claimed_by_mentor_id' => $newMentor->id,
                    'claimed_at'           => now(),
                ]);

            ActivityLogger::traineeAssigned($course, $trainee, $newMentor, $user);

            return $this->responseBuilder->build($course, $user);
        } catch (\Exception $e) {
            \Log::error('Error assigning trainee', [
                'mentor_id'     => $user->id,
                'trainee_id'    => $trainee->id,
                'course_id'     => $course->id,
                'new_mentor_id' => $request->mentor_id,
                'error'         => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while assigning the trainee.']);
        }
    }

    public function unclaimTrainee(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $course  = Course::findOrFail($request->course_id);
        $trainee = \App\Models\User::findOrFail($request->trainee_id);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot unclaim trainees for this course']);
        }

        if (!$course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
            return back()->withErrors(['error' => 'Trainee is not in this course']);
        }

        try {
            DB::table('course_trainees')
                ->where('course_id', $course->id)
                ->where('user_id', $trainee->id)
                ->update([
                    'claimed_by_mentor_id' => null,
                    'claimed_at'           => null,
                ]);

            ActivityLogger::traineeUnclaimed($course, $trainee, $user);

            return $this->responseBuilder->build($course, $user);
        } catch (\Exception $e) {
            \Log::error('Error unclaiming trainee', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while unclaiming the trainee.']);
        }
    }

    public function addMentor(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'user_id'   => 'required|integer|exists:users,id',
        ]);

        $course      = Course::findOrFail($request->course_id);
        $mentorToAdd = \App\Models\User::findOrFail($request->user_id);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        if (!$mentorToAdd->isMentor() && !$mentorToAdd->is_superuser && !$mentorToAdd->is_admin) {
            return back()->withErrors(['error' => 'This user does not have mentor privileges']);
        }

        try {
            if ($course->mentors()->where('user_id', $mentorToAdd->id)->exists()) {
                return back()->withErrors(['error' => 'This user is already a mentor for this course']);
            }

            $course->mentors()->attach($mentorToAdd->id);
            ActivityLogger::mentorAdded($course, $mentorToAdd, $user);

            return back()->with('success', "Successfully added {$mentorToAdd->name} as a mentor");
        } catch (\Exception $e) {
            \Log::error('Error adding mentor to course', [
                'admin_id'      => $user->id,
                'new_mentor_id' => $request->user_id,
                'course_id'     => $course->id,
                'error'         => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while adding the mentor.']);
        }
    }

    public function removeMentor(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'mentor_id' => 'required|integer|exists:users,id',
        ]);

        $course         = Course::findOrFail($request->course_id);
        $mentorToRemove = \App\Models\User::findOrFail($request->mentor_id);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        try {
            if ($course->mentors()->count() <= 1) {
                return back()->withErrors(['error' => 'Cannot remove the last mentor from a course']);
            }

            if (!$course->mentors()->where('user_id', $mentorToRemove->id)->exists()) {
                return back()->withErrors(['error' => 'This user is not a mentor for this course']);
            }

            $course->mentors()->detach($mentorToRemove->id);

            DB::table('course_trainees')
                ->where('course_id', $course->id)
                ->where('claimed_by_mentor_id', $mentorToRemove->id)
                ->update(['claimed_by_mentor_id' => null, 'claimed_at' => null]);

            ActivityLogger::mentorRemoved($course, $mentorToRemove, $user);

            return back()->with('success', "Successfully removed {$mentorToRemove->name} as a mentor");
        } catch (\Exception $e) {
            \Log::error('Error removing mentor from course', [
                'admin_id'  => $user->id,
                'mentor_id' => $request->mentor_id,
                'course_id' => $course->id,
                'error'     => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while removing the mentor.']);
        }
    }

    public function addTraineeToCourse(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'user_id'   => 'required|integer|exists:users,id',
        ]);

        $course  = Course::findOrFail($request->course_id);
        $trainee = \App\Models\User::findOrFail($request->user_id);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        try {
            if ($course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
                return back()->withErrors(['error' => 'This trainee is already active in this course']);
            }

            if (!$trainee->isVatsimUser()) {
                return back()->withErrors(['error' => 'This user does not have a VATSIM account']);
            }

            \App\Models\WaitingListEntry::where('user_id', $trainee->id)
                ->where('course_id', $course->id)
                ->delete();

            $existingCompleted = DB::table('course_trainees')
                ->where('course_id', $course->id)
                ->where('user_id', $trainee->id)
                ->whereNotNull('completed_at')
                ->exists();

            if ($existingCompleted) {
                DB::table('course_trainees')
                    ->where('course_id', $course->id)
                    ->where('user_id', $trainee->id)
                    ->update([
                        'completed_at'        => null,
                        'status'              => 'active',
                        'claimed_by_mentor_id' => $user->id,
                        'claimed_at'          => now(),
                        'updated_at'          => now(),
                    ]);
            } else {
                $course->activeTrainees()->attach($trainee->id, [
                    'claimed_by_mentor_id' => $user->id,
                    'claimed_at'           => now(),
                ]);

                if (!empty($course->moodle_course_ids)) {
                    try {
                        app(\App\Services\MoodleService::class)->enrollUserInCourses(
                            $trainee->vatsim_id,
                            $course->moodle_course_ids
                        );
                    } catch (\Exception $e) {
                        \Log::warning('Failed to enroll trainee in Moodle courses', [
                            'trainee_id' => $trainee->id,
                            'course_id'  => $course->id,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            }

            ActivityLogger::traineeAddedToCourse($course, $trainee, $user);

            return $this->responseBuilder->build($course, $user);
        } catch (\Exception $e) {
            \Log::error('Error adding trainee to course', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while adding the trainee.']);
        }
    }

    public function grantEndorsement(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor()) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $course  = Course::findOrFail($request->course_id);
        $trainee = \App\Models\User::findOrFail($request->trainee_id);

        if (!in_array($course->type, ['GST', 'EDMT']) && !($course->type === 'RTG' && $course->position === 'GND')) {
            return back()->withErrors(['error' => 'This course does not support endorsements']);
        }

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You are not a mentor for this course']);
        }

        try {
            if (!$course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
                return back()->withErrors(['error' => 'Trainee is not enrolled in this course']);
            }

            $endorsementGroups = $course->endorsementGroups();

            if ($endorsementGroups->isEmpty()) {
                return back()->withErrors(['error' => 'Course does not have any endorsement groups configured']);
            }

            $vatEudService      = app(\App\Services\VatEudService::class);
            $grantedEndorsements = [];
            $failedEndorsements  = [];

            foreach ($endorsementGroups as $groupName) {
                $result = $vatEudService->createTier1Endorsement($trainee->vatsim_id, $groupName, $user->vatsim_id);

                if ($result['success']) {
                    $grantedEndorsements[] = $groupName;
                    ActivityLogger::endorsementGranted($groupName, $trainee, $user, 'tier1');
                } else {
                    $failedEndorsements[] = ['name' => $groupName, 'error' => $result['message'] ?? 'Unknown error'];
                }
            }

            $vatEudService->refreshEndorsementCache();

            if (!empty($grantedEndorsements) && empty($failedEndorsements)) {
                return $this->responseBuilder->build($course, $user);
            }

            if (!empty($grantedEndorsements)) {
                $granted = implode(', ', $grantedEndorsements);
                $failed  = implode(', ', array_column($failedEndorsements, 'name'));
                return back()->with('warning', "Partially granted endorsements. Granted: {$granted}. Failed: {$failed}");
            }

            $errors = array_map(fn($f) => "{$f['name']}: {$f['error']}", $failedEndorsements);
            return back()->withErrors(['error' => 'Failed to grant endorsements: ' . implode('; ', $errors)]);

        } catch (\Exception $e) {
            \Log::error('Error granting endorsement', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while granting the endorsement. Please try again.']);
        }
    }

    public function finishCourse(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $course  = Course::findOrFail($request->course_id);
        $trainee = \App\Models\User::findOrFail($request->trainee_id);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        if (!$course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
            return back()->withErrors(['error' => 'Trainee is not in this course']);
        }

        try {
            DB::transaction(function () use ($course, $trainee, $user) {
                DB::table('course_trainees')
                    ->where('course_id', $course->id)
                    ->where('user_id', $trainee->id)
                    ->update(['completed_at' => now(), 'status' => 'completed']);

                $endorsementGroups = DB::table('course_endorsement_groups')
                    ->where('course_id', $course->id)
                    ->pluck('endorsement_group_name')
                    ->toArray();

                if (!empty($endorsementGroups)) {
                    $this->grantEndorsements($trainee, $endorsementGroups, $user);
                }

                if ($course->type === 'RTG' && $course->position === 'CTR') {
                    $this->addFIRFamiliarisations($trainee, $course, $user);
                } elseif ($course->type === 'FAM' && $course->familiarisation_sector_id) {
                    $this->addSingleFamiliarisation($trainee, $course, $user);
                }
            });

            ActivityLogger::courseFinished($course, $trainee, $user);

            return $this->responseBuilder->build($course, $user);
        } catch (\Exception $e) {
            \Log::error('Error finishing course', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while finishing the course. Please try again.']);
        }
    }

    public function getPastTrainees(Request $request, $courseId)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $course = Course::findOrFail($courseId);

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
            \Log::error('Error fetching past trainees', ['course_id' => $courseId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch past trainees'], 500);
        }
    }

    public function reactivateTrainee(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $course  = Course::findOrFail($request->course_id);
        $trainee = \App\Models\User::findOrFail($request->trainee_id);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        try {
            $completed = DB::table('course_trainees')
                ->where('course_id', $course->id)
                ->where('user_id', $trainee->id)
                ->whereNotNull('completed_at')
                ->exists();

            if (!$completed) {
                return back()->withErrors(['error' => 'Trainee has not completed this course']);
            }

            DB::table('course_trainees')
                ->where('course_id', $course->id)
                ->where('user_id', $trainee->id)
                ->update([
                    'completed_at'        => null,
                    'status'              => 'active',
                    'claimed_by_mentor_id' => $user->id,
                    'claimed_at'          => now(),
                ]);

            ActivityLogger::traineeReactivated($course, $trainee, $user);

            return $this->responseBuilder->build($course, $user);
        } catch (\Exception $e) {
            \Log::error('Error reactivating trainee', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while reactivating the trainee.']);
        }
    }

    protected function grantEndorsements(\App\Models\User $trainee, array $endorsementGroups, \App\Models\User $mentor): void
    {
        try {
            $vatEudService = app(\App\Services\VatEudService::class);

            $existing = collect($vatEudService->getTier1Endorsements())
                ->where('user_cid', $trainee->vatsim_id)
                ->pluck('position')
                ->toArray();

            foreach ($endorsementGroups as $position) {
                if (in_array($position, $existing)) {
                    continue;
                }

                $result = $vatEudService->createTier1Endorsement($trainee->vatsim_id, $position, $mentor->vatsim_id);

                if ($result['success']) {
                    ActivityLogger::endorsementGranted($position, $trainee, $mentor, 'tier1');
                } else {
                    \Log::warning('Failed to grant Tier 1 endorsement on course completion', [
                        'trainee_id' => $trainee->id,
                        'position'   => $position,
                        'error'      => $result['message'] ?? 'Unknown error',
                    ]);
                }
            }

            $vatEudService->refreshEndorsementCache();
        } catch (\Exception $e) {
            \Log::error('Error granting endorsements', [
                'trainee_id'        => $trainee->id,
                'endorsement_groups' => $endorsementGroups,
                'error'             => $e->getMessage(),
            ]);
        }
    }

    protected function addFIRFamiliarisations(\App\Models\User $trainee, Course $course, \App\Models\User $mentor): void
    {
        try {
            if (!$course->mentor_group_id) {
                \Log::warning('No mentor group for CTR course, cannot determine FIR', ['course_id' => $course->id]);
                return;
            }

            $mentorGroup = \App\Models\Role::find($course->mentor_group_id);
            if (!$mentorGroup) {
                \Log::warning('Mentor group not found', ['mentor_group_id' => $course->mentor_group_id]);
                return;
            }

            $fir     = substr($mentorGroup->name, 0, 4);
            $sectors = \App\Models\FamiliarisationSector::where('fir', $fir)->get();

            foreach ($sectors as $sector) {
                if (\App\Models\Familiarisation::where('user_id', $trainee->id)->where('familiarisation_sector_id', $sector->id)->exists()) {
                    continue;
                }

                \App\Models\Familiarisation::create([
                    'user_id'                    => $trainee->id,
                    'familiarisation_sector_id'  => $sector->id,
                ]);

                ActivityLogger::familiarisationAdded($trainee, $sector->name, $sector->id, $fir, $mentor, $course, true);
            }
        } catch (\Exception $e) {
            \Log::error('Error adding FIR familiarisations', [
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    protected function addSingleFamiliarisation(\App\Models\User $trainee, Course $course, \App\Models\User $mentor): void
    {
        try {
            $familiarisation = \App\Models\Familiarisation::firstOrCreate([
                'user_id'                   => $trainee->id,
                'familiarisation_sector_id' => $course->familiarisation_sector_id,
            ]);

            if ($familiarisation->wasRecentlyCreated) {
                $sector = \App\Models\FamiliarisationSector::find($course->familiarisation_sector_id);
                if ($sector) {
                    ActivityLogger::familiarisationAdded($trainee, $sector->name, $sector->id, $sector->fir, $mentor, $course, true);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error adding single familiarisation', [
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}