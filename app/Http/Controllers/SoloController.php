<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Services\VatEudService;
use App\Services\MoodleService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SoloController extends Controller
{
    protected VatEudService $vatEudService;
    protected MoodleService $moodleService;

    public function __construct(VatEudService $vatEudService, MoodleService $moodleService)
    {
        $this->vatEudService = $vatEudService;
        $this->moodleService = $moodleService;
    }

    private function checkMoodleCompletion(User $trainee, Course $course): array
    {
        if (empty($course->moodle_course_ids)) {
            return ['completed' => true, 'details' => []];
        }

        try {
            $allCompleted = true;
            $details = [];

            foreach ($course->moodle_course_ids as $moodleCourseId) {
                $completed = $this->moodleService->getCourseCompletion(
                    $trainee->vatsim_id,
                    $moodleCourseId
                );

                $details[] = [
                    'course_id' => $moodleCourseId,
                    'completed' => $completed
                ];

                if (!$completed) {
                    $allCompleted = false;
                }
            }

            return [
                'completed' => $allCompleted,
                'details' => $details
            ];
        } catch (\Exception $e) {
            Log::error('Failed to check Moodle completion for solo', [
                'trainee_id' => $trainee->id,
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);

            return [
                'completed' => false,
                'error' => 'Unable to verify Moodle completion'
            ];
        }
    }

    private function checkCoreTheoryStatus(User $trainee, Course $course): array
    {
        try {
            $coreTheoryIds = [
                'GND' => 6,
                'TWR' => 9,
                'APP' => 10,
                'CTR' => 11,
            ];

            if (!isset($coreTheoryIds[$course->position])) {
                return [
                    'status' => 'not_required',
                    'message' => 'Core theory test not required for this position'
                ];
            }

            $examId = $coreTheoryIds[$course->position];
            $exams = $this->vatEudService->getUserExams($trainee->vatsim_id);

            $passedExams = collect($exams['results'] ?? [])
                ->where('exam_id', $examId)
                ->where('passed', true)
                ->filter(function ($exam) {
                    $expiry = Carbon::parse($exam['expiry']);
                    return $expiry->isFuture();
                });

            if ($passedExams->isNotEmpty()) {
                return [
                    'status' => 'passed',
                    'exam_id' => $examId
                ];
            }

            $assignments = collect($exams['assignments'] ?? [])
                ->where('exam_id', $examId)
                ->filter(function ($assignment) {
                    $expires = Carbon::parse($assignment['expires']);
                    return $expires->isFuture();
                });

            if ($assignments->isNotEmpty()) {
                return [
                    'status' => 'assigned',
                    'exam_id' => $examId
                ];
            }

            return [
                'status' => 'not_assigned',
                'exam_id' => $examId
            ];
        } catch (\Exception $e) {
            Log::error('Failed to check core theory status for solo', [
                'trainee_id' => $trainee->id,
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => 'Unable to verify core theory test status'
            ];
        }
    }

    public function getSoloRequirements(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $trainee = User::findOrFail($request->trainee_id);
        $course = Course::findOrFail($request->course_id);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $moodleStatus = $this->checkMoodleCompletion($trainee, $course);
        $coreTheoryStatus = $this->checkCoreTheoryStatus($trainee, $course);

        return response()->json([
            'trainee_id' => $trainee->id,
            'moodle' => $moodleStatus,
            'core_theory' => $coreTheoryStatus,
            'solo_days_used' => $trainee->solo_days_used,
            'can_grant_solo' => $moodleStatus['completed'] &&
                in_array($coreTheoryStatus['status'], ['passed', 'not_required'])
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
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $trainee = User::findOrFail($request->trainee_id);
        $course = Course::findOrFail($request->course_id);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        try {
            $coreTheoryStatus = $this->checkCoreTheoryStatus($trainee, $course);

            if ($coreTheoryStatus['status'] === 'not_required') {
                return response()->json([
                    'success' => false,
                    'message' => 'Core theory test not required for this position'
                ]);
            }

            if ($coreTheoryStatus['status'] !== 'not_assigned') {
                return response()->json([
                    'success' => false,
                    'message' => 'Core theory test already assigned or passed'
                ]);
            }

            $result = $this->vatEudService->assignCoreTheoryTest(
                $trainee->vatsim_id,
                $coreTheoryStatus['exam_id'],
                $user->vatsim_id
            );

            if ($result['success']) {
                ActivityLogger::coreTestAssigned($trainee, $course, $user);
                return response()->json([
                    'success' => true,
                    'message' => 'Core theory test assigned successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to assign core theory test'
            ]);
        } catch (\Exception $e) {
            Log::error('Error assigning core theory test', [
                'mentor_id' => $user->id,
                'trainee_id' => $trainee->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while assigning the core theory test'
            ], 500);
        }
    }

    public function addSolo(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
            'expiry_date' => 'required|date|after:today',
        ]);

        $trainee = User::findOrFail($request->trainee_id);
        $course = Course::findOrFail($request->course_id);

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
        $maxDate = Carbon::now()->addDays(31);

        if ($expiryDate->greaterThan($maxDate)) {
            return back()->withErrors(['error' => 'Solo endorsement cannot exceed 31 days']);
        }

        $soloDays = $expiryDate->diffInDays(Carbon::now()) + 1;

        if ($trainee->solo_days_remaining < $soloDays) {
            return back()->withErrors(['error' => "Trainee has only {$trainee->solo_days_remaining} solo days remaining (needs {$soloDays} days)"]);
        }

        $existingSolos = $this->vatEudService->getSoloEndorsements();
        $hasSolo = collect($existingSolos)->first(function ($solo) use ($trainee, $course) {
            return $solo['user_cid'] == $trainee->vatsim_id && 
                   $solo['position'] === $course->solo_station;
        });

        if ($hasSolo) {
            return back()->withErrors(['error' => 'Trainee already has a solo endorsement for this position']);
        }

        try {
            $expiryDateTime = $expiryDate->setTime(23, 59, 0);
            $formattedExpiry = $expiryDateTime->format('Y-m-d\TH:i:s.v\Z');

            $result = $this->vatEudService->createSoloEndorsement(
                $trainee->vatsim_id,
                $course->solo_station,
                $formattedExpiry,
                $user->vatsim_id
            );

            if ($result['success']) {
                $trainee->increment('solo_days_used', $soloDays);

                $this->vatEudService->refreshEndorsementCache();
                ActivityLogger::soloGranted($course->solo_station, $trainee, $user, $formattedExpiry);

                return back()->with('success', "Successfully granted solo endorsement for {$course->solo_station} to {$trainee->name} ({$soloDays} days, {$trainee->solo_days_remaining} remaining)");
            } else {
                return back()->withErrors(['error' => $result['message'] ?? 'Failed to grant solo endorsement']);
            }

        } catch (\Exception $e) {
            Log::error('Error granting solo endorsement', [
                'mentor_id' => $user->id,
                'trainee_id' => $trainee->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
            'expiry_date' => 'required|date|after:today',
        ]);

        $trainee = User::findOrFail($request->trainee_id);
        $course = Course::findOrFail($request->course_id);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return back()->withErrors(['error' => 'You cannot manage this course']);
        }

        $expiryDate = Carbon::parse($request->expiry_date);
        $maxDate = Carbon::now()->addDays(31);

        if ($expiryDate->greaterThan($maxDate)) {
            return back()->withErrors(['error' => 'Solo endorsement cannot exceed 31 days']);
        }

        try {
            $existingSolos = $this->vatEudService->getSoloEndorsements();
            $solo = collect($existingSolos)->first(function ($s) use ($trainee, $course) {
                return $s['user_cid'] == $trainee->vatsim_id && 
                       $s['position'] === $course->solo_station;
            });

            if (!$solo) {
                return back()->withErrors(['error' => 'No solo endorsement found for this trainee and position']);
            }

            $currentExpiry = Carbon::parse($solo['expiry']);
            $newSoloDays = $expiryDate->diffInDays($currentExpiry);

            if ($newSoloDays > 0 && $trainee->solo_days_remaining < $newSoloDays) {
                return back()->withErrors(['error' => "Trainee has only {$trainee->solo_days_remaining} solo days remaining (needs {$newSoloDays} additional days)"]);
            }

            $this->vatEudService->removeSoloEndorsement($solo['id']);

            $expiryDateTime = $expiryDate->setTime(23, 59, 0);
            $formattedExpiry = $expiryDateTime->format('Y-m-d\TH:i:s.v\Z');

            $result = $this->vatEudService->createSoloEndorsement(
                $trainee->vatsim_id,
                $course->solo_station,
                $formattedExpiry,
                $user->vatsim_id
            );

            if ($result['success']) {
                if ($newSoloDays > 0) {
                    $trainee->increment('solo_days_used', $newSoloDays);
                }

                $this->vatEudService->refreshEndorsementCache();
                ActivityLogger::soloExtended($course->solo_station, $trainee, $user, $formattedExpiry);

                return back()->with('success', "Successfully extended solo endorsement for {$trainee->name} ({$trainee->solo_days_remaining} days remaining)");
            } else {
                return back()->withErrors(['error' => $result['message'] ?? 'Failed to extend solo endorsement']);
            }

        } catch (\Exception $e) {
            Log::error('Error extending solo endorsement', [
                'mentor_id' => $user->id,
                'trainee_id' => $trainee->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
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
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $trainee = User::findOrFail($request->trainee_id);
        $course = Course::findOrFail($request->course_id);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return back()->withErrors(['error' => 'You cannot manage this course']);
        }

        try {
            $existingSolos = $this->vatEudService->getSoloEndorsements();
            $solo = collect($existingSolos)->first(function ($s) use ($trainee, $course) {
                return $s['user_cid'] == $trainee->vatsim_id && 
                       $s['position'] === $course->solo_station;
            });

            if (!$solo) {
                return back()->withErrors(['error' => 'No solo endorsement found for this trainee and position']);
            }

            $success = $this->vatEudService->removeSoloEndorsement($solo['id']);

            if ($success) {
                $this->vatEudService->refreshEndorsementCache();
                ActivityLogger::soloRemoved($course->solo_station, $trainee, $user);

                return back()->with('success', "Successfully removed solo endorsement for {$trainee->name}");
            } else {
                return back()->withErrors(['error' => 'Failed to remove solo endorsement']);
            }

        } catch (\Exception $e) {
            Log::error('Error removing solo endorsement', [
                'mentor_id' => $user->id,
                'trainee_id' => $trainee->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while removing the solo endorsement']);
        }
    }
}