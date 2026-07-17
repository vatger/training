<?php

namespace App\Http\Controllers\Training;

use App\Domain\Training\Actions\AddMentorToCourse;
use App\Domain\Training\Actions\RemoveMentorFromCourse;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseValidationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class MentorManagementController extends Controller
{
    public function __construct(
        private AddMentorToCourse      $addMentorToCourse,
        private RemoveMentorFromCourse $removeMentorFromCourse,
        private CourseValidationService $courseValidationService,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();

        $moodleClient = app(\App\Integrations\Moodle\MoodleClient::class);
        $moodleSignedUp = $moodleClient->userExists($user->vatsim_id);

        $isAdmin = $user->is_admin || $user->is_superuser;
        $isOnRoster = $this->courseValidationService->isUserOnRoster($user->vatsim_id);
        $isGerSubdivision = $user->subdivision === 'GER';
        $isVisitor = !$isGerSubdivision && $isOnRoster;

        $userEndorsements = $this->courseValidationService->getUserEndorsements($user->vatsim_id);
        $userFamSectorIds = \App\Models\Familiarisation::where('user_id', $user->id)
            ->pluck('familiarisation_sector_id')
            ->all();

        $courses = Course::with('mentorGroup')->get()->map(function ($course) use ($user, $isAdmin, $isGerSubdivision, $isOnRoster, $isVisitor, $userEndorsements, $userFamSectorIds) {
            $waitingEntry = \App\Models\WaitingListEntry::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->first();

            $isOnWaitingList = (bool) $waitingEntry;
            $waitingPosition = null;

            if ($isOnWaitingList) {
                $waitingPosition = \App\Models\WaitingListEntry::where('course_id', $course->id)
                    ->where('date_added', '<=', $waitingEntry->date_added)
                    ->count();
            }

            if (!$isAdmin && !$isOnWaitingList && !$this->isCourseVisibleToUser($course, $isGerSubdivision, $isOnRoster, $isVisitor, $user->rating, $userEndorsements, $userFamSectorIds)) {
                return null;
            }

            [$canJoin, $joinError] = $this->courseValidationService->canUserJoinCourse($course, $user);

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
                'waiting_list_position' => $waitingPosition,
                'waiting_list_activity' => $waitingEntry?->activity,
                'can_join' => $canJoin,
                'join_error' => $joinError ?: null,
            ];
        })->filter();

        $userHasActiveRtgCourse = \App\Models\WaitingListEntry::where('user_id', $user->id)
            ->whereHas('course', fn($q) => $q->where('type', 'RTG'))
            ->exists();

        return Inertia::render('training/courses', [
            'courses' => $courses->values(),
            'isVatsimUser' => $user->isVatsimUser(),
            'moodleSignedUp' => $moodleSignedUp,
            'userHasActiveRtgCourse' => $userHasActiveRtgCourse,
        ]);
    }

    public function toggleWaitingList(Request $request, Course $course)
    {
        $user = $request->user();

        $existing = \App\Models\WaitingListEntry::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Removed from waiting list.');
        }

        [$canJoin, $error] = $this->courseValidationService->canUserJoinCourse($course, $user);

        if (!$canJoin) {
            return back()->withErrors(['join' => $error]);
        }

        \App\Models\WaitingListEntry::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'date_added' => now(),
            'activity' => 0,
        ]);

        return back()->with('success', 'Added to waiting list.');
    }

    public function addMentor(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'user_id'   => 'required|integer|exists:users,id',
        ]);

        $course      = Course::findOrFail($validated['course_id']);
        $mentorToAdd = User::findOrFail($validated['user_id']);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        if (!$mentorToAdd->isMentor() && !$mentorToAdd->is_superuser && !$mentorToAdd->is_admin) {
            return back()->withErrors(['error' => 'This user does not have mentor privileges']);
        }

        if ($course->mentors()->where('user_id', $mentorToAdd->id)->exists()) {
            return back()->withErrors(['error' => 'This user is already a mentor for this course']);
        }

        try {
            $this->addMentorToCourse->execute($course, $mentorToAdd, $user);
            return back()->with('success', "Successfully added {$mentorToAdd->name} as a mentor");
        } catch (\Exception $e) {
            Log::error('Error adding mentor to course', ['admin_id' => $user->id, 'new_mentor_id' => $validated['user_id'], 'course_id' => $course->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'An error occurred while adding the mentor.']);
        }
    }

    private function isCourseVisibleToUser(
        Course $course,
        bool $isGerSubdivision,
        bool $isOnRoster,
        bool $isVisitor,
        int $userRating,
        \Illuminate\Support\Collection $userEndorsements,
        array $userFamSectorIds,
    ): bool {
        if ($isGerSubdivision && $isOnRoster) {
            if ($course->type === 'RST' || $course->type === 'GST') {
                return false;
            }
        } elseif ($isGerSubdivision && !$isOnRoster) {
            if ($course->type !== 'RST') {
                return false;
            }
        } elseif ($isVisitor) {
            if ($course->type === 'RST' || $course->type === 'GST') {
                return false;
            }
        } else {
            if ($course->type !== 'GST') {
                return false;
            }
        }

        if ($course->type !== 'GST' && !($course->min_rating <= $userRating && $userRating <= $course->max_rating)) {
            return false;
        }

        if ($course->type === 'EDMT') {
            $endorsementGroups = $course->endorsementGroups();
            if ($endorsementGroups->isNotEmpty() && $endorsementGroups->every(fn ($g) => $userEndorsements->contains($g))) {
                return false;
            }
        }

        if ($course->familiarisation_sector_id && in_array($course->familiarisation_sector_id, $userFamSectorIds)) {
            return false;
        }

        return true;
    }

    public function removeMentor(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'mentor_id' => 'required|integer|exists:users,id',
        ]);

        $course         = Course::findOrFail($validated['course_id']);
        $mentorToRemove = User::findOrFail($validated['mentor_id']);

        if (!$user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        if ($course->mentors()->count() <= 1) {
            return back()->withErrors(['error' => 'Cannot remove the last mentor from a course']);
        }

        if (!$course->mentors()->where('user_id', $mentorToRemove->id)->exists()) {
            return back()->withErrors(['error' => 'This user is not a mentor for this course']);
        }

        try {
            $this->removeMentorFromCourse->execute($course, $mentorToRemove, $user);
            return back()->with('success', "Successfully removed {$mentorToRemove->name} as a mentor");
        } catch (\Exception $e) {
            Log::error('Error removing mentor from course', ['admin_id' => $user->id, 'mentor_id' => $validated['mentor_id'], 'course_id' => $course->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'An error occurred while removing the mentor.']);
        }
    }
}