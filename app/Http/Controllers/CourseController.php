<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Services\CourseValidationService;
use App\Services\WaitingListService;
use App\Services\FamiliarisationService;
use App\Services\MoodleService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    protected CourseValidationService $validationService;
    protected WaitingListService $waitingListService;
    protected FamiliarisationService $familiarisationService;
    protected MoodleService $moodleService;

    public function __construct(
        CourseValidationService $validationService,
        WaitingListService $waitingListService,
        FamiliarisationService $familiarisationService,
        MoodleService $moodleService
    ) {
        $this->validationService = $validationService;
        $this->waitingListService = $waitingListService;
        $this->familiarisationService = $familiarisationService;
        $this->moodleService = $moodleService;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $moodleSignedUp = $this->moodleService->userExists($user->vatsim_id);

        try {
            if ($user->is_admin || $user->is_superuser) {
                $courses = Course::with(['mentorGroup', 'familiarisationSector'])->get();
                $filteredCourses = $courses;
            } else {
                $courses = Course::forRating(rating: $user->rating)
                    ->availableFor($user)
                    ->with(['mentorGroup', 'familiarisationSector'])
                    ->get();

                $filteredCourses = $this->filterCoursesForUser($courses, $user);
            }

            $waitingListEntries = WaitingListEntry::where('user_id', $user->id)
                ->pluck('activity', 'course_id');

            $waitingListPositions = \DB::table('waiting_list_entries as wle1')
                ->select('wle1.course_id', \DB::raw('COUNT(DISTINCT wle2.id) + 1 as position'))
                ->leftJoin('waiting_list_entries as wle2', function ($join) use ($user) {
                    $join->on('wle1.course_id', '=', 'wle2.course_id')
                        ->whereRaw('wle2.date_added < wle1.date_added');
                })
                ->where('wle1.user_id', $user->id)
                ->groupBy('wle1.course_id')
                ->pluck('position', 'course_id');

            $userHasActiveRtgCourse = \Cache::remember(
                "user_{$user->id}_active_rtg_course",
                now()->addMinutes(5),
                fn() => $user->activeRatingCourses()
                    ->wherePivot('completed_at', null)
                    ->exists() ||
                $user->waitingListEntries()->whereHas('course', function ($q) {
                    $q->where('type', 'RTG');
                })->exists()
            );

            $formattedCourses = $filteredCourses->map(function ($course) use ($user, $waitingListEntries, $waitingListPositions) {
                $isOnWaitingList = $waitingListEntries->has($course->id);
                [$canJoin, $joinError] = $this->validationService->canUserJoinCourse($course, $user);

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'trainee_display_name' => $course->trainee_display_name,
                    'description' => $course->description,
                    'airport_name' => $course->airport_name,
                    'airport_icao' => $course->airport_icao,
                    'type' => $course->type,
                    'type_display' => $course->type_display,
                    'position' => $course->position,
                    'position_display' => $course->position_display,
                    'mentor_group' => $course->mentorGroup?->name,
                    'min_rating' => $course->min_rating,
                    'max_rating' => $course->max_rating,
                    'is_on_waiting_list' => $isOnWaitingList,
                    'waiting_list_position' => $waitingListPositions[$course->id] ?? null,
                    'waiting_list_activity' => $waitingListEntries[$course->id] ?? null,
                    'can_join' => $canJoin,
                    'join_error' => $joinError,
                ];
            });

            return Inertia::render('training/courses', [
                'courses' => $formattedCourses,
                'isVatsimUser' => true,
                'moodleSignedUp' => $moodleSignedUp,
                'userHasActiveRtgCourse' => $userHasActiveRtgCourse,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to load courses', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('training/courses', [
                'courses' => [],
                'isVatsimUser' => true,
                'moodleSignedUp' => $moodleSignedUp ?? false,
                'error' => 'Failed to load courses. Please try again.',
            ]);
        }
    }

    public function toggleWaitingList(Request $request, Course $course): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!$user->isVatsimUser()) {
            return back()->with('flash', [
                'success' => false,
                'message' => 'VATSIM account required',
                'action' => 'error'
            ]);
        }

        if (!$this->moodleService->userExists($user->vatsim_id)) {
            return back()->with('flash', [
                'success' => false,
                'message' => 'You must sign up on Moodle before joining a waiting list',
                'action' => 'error'
            ]);
        }

        try {
            $entry = WaitingListEntry::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($entry) {
                [$success, $message] = $this->waitingListService->leaveWaitingList($course, $user);

                \Cache::forget("user_{$user->id}_active_rtg_course");

                return back()->with('flash', [
                    'success' => $success,
                    'message' => $message,
                    'action' => $success ? 'left' : 'error',
                ]);
            } else {
                [$success, $message] = $this->waitingListService->joinWaitingList($course, $user);

                if ($success) {
                    $newEntry = WaitingListEntry::where('user_id', $user->id)
                        ->where('course_id', $course->id)
                        ->first();

                    \Cache::forget("user_{$user->id}_active_rtg_course");

                    return back()->with('flash', [
                        'success' => true,
                        'message' => $message,
                        'action' => 'joined',
                        'position' => $newEntry ? $newEntry->position_in_queue : 1,
                    ]);
                } else {
                    return back()->with('flash', [
                        'success' => false,
                        'message' => 'An error occurred. Please try again.',
                        'action' => 'error'
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error in toggleWaitingList', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('flash', [
                'success' => false,
                'message' => 'An error occurred. Please try again.',
                'action' => 'error'
            ]);
        }
    }

    protected function filterCoursesForUser($courses, User $user)
    {
        return $courses->filter(function ($course) use ($user) {
            // Determine roster status
            try {
                $isOnRoster = $this->validationService->isUserOnRoster($user->vatsim_id);
            } catch (\Exception $e) {
                // If roster check fails, assume not on roster for safety
                $isOnRoster = false;
            }

            $isGerSubdivision = $user->subdivision === 'GER';

            // Filter based on user type
            if ($isGerSubdivision && $isOnRoster) {
                // Regular VATGER members: Can see RTG, EDMT, FAM - NOT RST or GST
                if ($course->type === 'RST')
                    return false;
                if ($course->type === 'GST')
                    return false;
            } elseif ($isGerSubdivision && !$isOnRoster) {
                // GER subdivision but not on roster: ONLY RST courses
                if ($course->type !== 'RST')
                    return false;
            } else {
                // Non-GER subdivision (visitors): ONLY GST courses
                if ($course->type !== 'GST')
                    return false;
            }

            // Existing RTG restriction logic
            if ($course->type === 'RTG') {
                $hasActiveRtg = $user->activeRatingCourses()
                    ->wherePivot('completed_at', null)
                    ->exists();

                if ($hasActiveRtg) {
                    return false;
                }
            }

            // Existing S3 APP restriction logic
            if ($user->rating === 3 && $course->type === 'RTG' && $course->position === 'APP') {
                $minDays = config('services.training.s3_rating_change_days', 90);
                if ($user->last_rating_change) {
                    $daysSinceRatingChange = \Carbon\Carbon::parse($user->last_rating_change)->diffInDays(now());
                    if ($daysSinceRatingChange < $minDays) {
                        return false;
                    }
                }
            }

            return true;
        });
    }
}