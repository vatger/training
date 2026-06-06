<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Services\VatEudService;
use App\Services\MoodleService;
use App\Services\ActivityLogger;
use App\Services\MentorCourseResponseBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SoloController extends Controller
{
    public function __construct(
        private VatEudService $vatEudService,
        private MoodleService $moodleService,
        private MentorCourseResponseBuilder $responseBuilder,
    ) {}

    private function checkMoodleCompletion(User $trainee, Course $course): array
    {
        if (empty($course->moodle_course_ids)) {
            return ['completed' => true, 'details' => []];
        }

        try {
            $allCompleted = true;
            $details      = [];

            foreach ($course->moodle_course_ids as $moodleCourseId) {
                $completed = $this->moodleService->getCourseCompletion($trainee->vatsim_id, $moodleCourseId);
                $details[] = ['course_id' => $moodleCourseId, 'completed' => $completed];

                if (!$completed) {
                    $allCompleted = false;
                }
            }

            return ['completed' => $allCompleted, 'details' => $details];
        } catch (\Exception $e) {
            Log::error('Failed to check Moodle completion for solo', [
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);

            return ['completed' => false, 'error' => 'Unable to verify Moodle completion'];
        }
    }

    private function checkCoreTheoryStatus(User $trainee, Course $course): array
    {
        try {
            $coreTheoryIds = ['GND' => 6, 'TWR' => 9, 'APP' => 10, 'CTR' => 11];

            if (!isset($coreTheoryIds[$course->position])) {
                return ['status' => 'not_required', 'message' => 'Core theory test not required for this position'];
            }

            $examId = $coreTheoryIds[$course->position];
            $exams  = $this->vatEudService->getUserExams($trainee->vatsim_id);

            $passed = collect($exams['results'] ?? [])
                ->where('exam_id', $examId)
                ->where('passed', true)
                ->filter(fn($e) => Carbon::parse($e['expiry'])->isFuture());

            if ($passed->isNotEmpty()) {
                return ['status' => 'passed', 'exam_id' => $examId];
            }

            $assigned = collect($exams['assignments'] ?? [])
                ->where('exam_id', $examId)
                ->filter(fn($a) => Carbon::parse($a['expires'])->isFuture());

            if ($assigned->isNotEmpty()) {
                return ['status' => 'assigned', 'exam_id' => $examId];
            }

            return ['status' => 'not_assigned', 'exam_id' => $examId];
        } catch (\Exception $e) {
            Log::error('Failed to check core theory status for solo', [
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => 'Unable to verify core theory test status'];
        }
    }

    public function getSoloRequirements(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $trainee = User::findOrFail($request->input('trainee_id'));
        $course  = Course::findOrFail($request->input('course_id'));

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $moodleStatus     = $this->checkMoodleCompletion($trainee, $course);
        $coreTheoryStatus = $this->checkCoreTheoryStatus($trainee, $course);

        return response()->json([
            'trainee_id'     => $trainee->id,
            'moodle'         => $moodleStatus,
            'core_theory'    => $coreTheoryStatus,
            'can_grant_solo' => $moodleStatus['completed'] &&
                in_array($coreTheoryStatus['status'], ['passed', 'not_required']),
        ]);
    }

    public function assignCoreTest(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $trainee = User::findOrFail($request->trainee_id);
        $course  = Course::findOrFail($request->course_id);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        try {
            $coreTheoryStatus = $this->checkCoreTheoryStatus($trainee, $course);

            if ($coreTheoryStatus['status'] === 'not_required') {
                return response()->json(['success' => false, 'message' => 'Core theory test not required for this position']);
            }

            if ($coreTheoryStatus['status'] !== 'not_assigned') {
                return response()->json(['success' => false, 'message' => 'Core theory test already assigned or passed']);
            }

            $result = $this->vatEudService->assignCoreTheoryTest(
                $trainee->vatsim_id,
                $coreTheoryStatus['exam_id'],
                $user->vatsim_id
            );

            if ($result['success']) {
                ActivityLogger::coreTestAssigned($trainee, $course, $user);
                return response()->json(['success' => true, 'message' => 'Core theory test assigned successfully']);
            }

            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Failed to assign core theory test']);
        } catch (\Exception $e) {
            Log::error('Error assigning core theory test', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'An error occurred while assigning the core theory test'], 500);
        }
    }

    public function addSolo(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id'  => 'required|integer|exists:users,id',
            'course_id'   => 'required|integer|exists:courses,id',
            'expiry_date' => 'required|date|after:today',
        ]);

        $trainee = User::findOrFail($request->trainee_id);
        $course  = Course::findOrFail($request->course_id);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return back()->withErrors(['error' => 'You cannot manage this course']);
        }

        if ($course->type !== 'RTG') {
            return back()->withErrors(['error' => 'Solo endorsements can only be granted for rating courses']);
        }

        if (empty($course->solo_station)) {
            return back()->withErrors(['error' => 'This course does not have a solo station configured']);
        }

        $moodleStatus = $this->checkMoodleCompletion($trainee, $course);
        if (!$moodleStatus['completed']) {
            return back()->withErrors(['error' => 'Trainee has not completed all required Moodle courses']);
        }

        $coreTheoryStatus = $this->checkCoreTheoryStatus($trainee, $course);
        if (!in_array($coreTheoryStatus['status'], ['passed', 'not_required'])) {
            return back()->withErrors(['error' => 'Trainee has not passed the required core theory test']);
        }

        $expiryDate = Carbon::parse($request->expiry_date);

        if ($expiryDate->greaterThan(Carbon::now()->addDays(31))) {
            return back()->withErrors(['error' => 'Solo endorsement cannot exceed 31 days']);
        }

        $hasSolo = collect($this->vatEudService->getSoloEndorsements())->first(
            fn($s) => $s['user_cid'] == $trainee->vatsim_id && $s['position'] === $course->solo_station
        );

        if ($hasSolo) {
            return back()->withErrors(['error' => 'Trainee already has a solo endorsement for this position']);
        }

        try {
            $formattedExpiry = $expiryDate->setTime(23, 59, 0)->format('Y-m-d\TH:i:s.v\Z');

            $result = $this->vatEudService->createSoloEndorsement(
                $trainee->vatsim_id,
                $course->solo_station,
                $formattedExpiry,
                $user->vatsim_id
            );

            if ($result['success']) {
                $this->vatEudService->refreshEndorsementCache();
                ActivityLogger::soloGranted($course->solo_station, $trainee, $user, $formattedExpiry);

                return $this->responseBuilder->build($course, $user);
            }

            return back()->withErrors(['error' => $result['message'] ?? 'Failed to grant solo endorsement']);
        } catch (\Exception $e) {
            Log::error('Error granting solo endorsement', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while granting the solo endorsement']);
        }
    }

    public function extendSolo(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id'  => 'required|integer|exists:users,id',
            'course_id'   => 'required|integer|exists:courses,id',
            'expiry_date' => 'required|date|after:today',
        ]);

        $trainee = User::findOrFail($request->trainee_id);
        $course  = Course::findOrFail($request->course_id);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return back()->withErrors(['error' => 'You cannot manage this course']);
        }

        $expiryDate = Carbon::parse($request->expiry_date);

        if ($expiryDate->greaterThan(Carbon::now()->addDays(31))) {
            return back()->withErrors(['error' => 'Solo endorsement cannot exceed 31 days']);
        }

        try {
            $solo = collect($this->vatEudService->getSoloEndorsements())->first(
                fn($s) => $s['user_cid'] == $trainee->vatsim_id && $s['position'] === $course->solo_station
            );

            if (!$solo) {
                return back()->withErrors(['error' => 'No solo endorsement found for this trainee and position']);
            }

            $this->vatEudService->removeSoloEndorsement($solo['id']);

            $formattedExpiry = $expiryDate->setTime(23, 59, 0)->format('Y-m-d\TH:i:s.v\Z');

            $result = $this->vatEudService->createSoloEndorsement(
                $trainee->vatsim_id,
                $course->solo_station,
                $formattedExpiry,
                $user->vatsim_id
            );

            if ($result['success']) {
                $this->vatEudService->refreshEndorsementCache();
                ActivityLogger::soloExtended($course->solo_station, $trainee, $user, $formattedExpiry);

                return $this->responseBuilder->build($course, $user);
            }

            return back()->withErrors(['error' => $result['message'] ?? 'Failed to extend solo endorsement']);
        } catch (\Exception $e) {
            Log::error('Error extending solo endorsement', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while extending the solo endorsement']);
        }
    }

    public function removeSolo(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $trainee = User::findOrFail($request->trainee_id);
        $course  = Course::findOrFail($request->course_id);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return back()->withErrors(['error' => 'You cannot manage this course']);
        }

        try {
            $solo = collect($this->vatEudService->getSoloEndorsements())->first(
                fn($s) => $s['user_cid'] == $trainee->vatsim_id && $s['position'] === $course->solo_station
            );

            if (!$solo) {
                return back()->withErrors(['error' => 'No solo endorsement found for this trainee and position']);
            }

            $success = $this->vatEudService->removeSoloEndorsement($solo['id']);

            if ($success) {
                $this->vatEudService->refreshEndorsementCache();
                ActivityLogger::soloRemoved($course->solo_station, $trainee, $user);

                return $this->responseBuilder->build($course, $user);
            }

            return back()->withErrors(['error' => 'Failed to remove solo endorsement']);
        } catch (\Exception $e) {
            Log::error('Error removing solo endorsement', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while removing the solo endorsement']);
        }
    }
}