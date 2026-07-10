<?php

namespace App\Http\Controllers\Solo;

use App\Domain\Solo\Actions\ExtendSoloEndorsement;
use App\Domain\Solo\Actions\GrantSoloEndorsement;
use App\Domain\Solo\Actions\RemoveSoloEndorsement;
use App\Http\Controllers\Controller;
use App\Integrations\Moodle\MoodleClient;
use App\Models\Course;
use App\Models\User;
use App\Integrations\VatEud\VatEudService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SoloController extends Controller
{
    public function __construct(
        private GrantSoloEndorsement $grantSolo,
        private ExtendSoloEndorsement $extendSolo,
        private RemoveSoloEndorsement $removeSolo,
        private VatEudService $vatEudService,
        private MoodleClient $moodleClient,
    ) {}

    public function getSoloRequirements(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $trainee = User::findOrFail($validated['trainee_id']);
        $course = Course::findOrFail($validated['course_id']);

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

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $trainee = User::findOrFail($validated['trainee_id']);
        $course = Course::findOrFail($validated['course_id']);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $coreTheoryStatus = $this->checkCoreTheoryStatus($trainee, $course);

        if ($coreTheoryStatus['status'] === 'not_required') {
            return response()->json(['success' => false, 'message' => 'Core theory test not required for this position']);
        }

        if ($coreTheoryStatus['status'] !== 'not_assigned') {
            return response()->json(['success' => false, 'message' => 'Core theory test already assigned or passed']);
        }

        try {
            $result = $this->vatEudService->assignCoreTheoryTest(
                $trainee->vatsim_id,
                $coreTheoryStatus['exam_id'],
                $user->vatsim_id,
            );

            if ($result['success']) {
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

        $validated = $request->validate([
            'trainee_id'  => 'required|integer|exists:users,id',
            'course_id'   => 'required|integer|exists:courses,id',
            'expiry_date' => ['required', 'date', 'after:' . now()->addDays(6)->format('Y-m-d')],
        ]);

        $trainee = User::findOrFail($validated['trainee_id']);
        $course = Course::findOrFail($validated['course_id']);
        $expiryDate = Carbon::parse($validated['expiry_date']);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return back()->withErrors(['error' => 'You cannot manage this course']);
        }

        if ($course->type !== 'RTG') {
            return back()->withErrors(['error' => 'Solo endorsements can only be granted for rating courses']);
        }

        if (empty($course->solo_station)) {
            return back()->withErrors(['error' => 'This course does not have a solo station configured']);
        }

        if ($expiryDate->greaterThan(Carbon::now()->addDays(31))) {
            return back()->withErrors(['error' => 'Solo endorsement cannot exceed 31 days']);
        }

        try {
            $this->grantSolo->execute($course, $trainee, $user, $expiryDate);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Error granting solo endorsement', [
                'mentor_id'  => $user->id,
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error' => $e->getMessage(),
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

        $validated = $request->validate([
            'trainee_id'  => 'required|integer|exists:users,id',
            'course_id'   => 'required|integer|exists:courses,id',
            'expiry_date' => ['required', 'date', 'after:' . now()->addDays(6)->format('Y-m-d')],
        ]);

        $trainee = User::findOrFail($validated['trainee_id']);
        $course = Course::findOrFail($validated['course_id']);
        $expiryDate = Carbon::parse($validated['expiry_date']);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return back()->withErrors(['error' => 'You cannot manage this course']);
        }

        if ($expiryDate->greaterThan(Carbon::now()->addDays(31))) {
            return back()->withErrors(['error' => 'Solo endorsement cannot exceed 31 days']);
        }

        try {
            $this->extendSolo->execute($course, $trainee, $user, $expiryDate);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
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

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id'  => 'required|integer|exists:courses,id',
        ]);

        $trainee = User::findOrFail($validated['trainee_id']);
        $course = Course::findOrFail($validated['course_id']);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return back()->withErrors(['error' => 'You cannot manage this course']);
        }

        try {
            $this->removeSolo->execute($course, $trainee, $user);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
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

    private function checkMoodleCompletion(User $trainee, Course $course): array
    {
        if (empty($course->moodle_course_ids)) {
            return ['completed' => true, 'details' => []];
        }

        try {
            $allCompleted = true;
            $details = [];

            foreach ($course->moodle_course_ids as $moodleCourseId) {
                $completed = $this->moodleClient->getCourseCompletion($trainee->vatsim_id, $moodleCourseId);
                $details[] = ['course_id' => $moodleCourseId, 'completed' => $completed];

                if (!$completed) {
                    $allCompleted = false;
                }
            }

            return ['completed' => $allCompleted, 'details' => $details];
        } catch (\Exception $e) {
            Log::error('Failed to check Moodle completion for solo', [
                'trainee_id' => $trainee->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
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
            $exams = $this->vatEudService->getUserExams($trainee->vatsim_id);

            $passed = collect($exams->results)
                ->filter(fn($r) => $r->examId === $examId && $r->passed && $r->expiry->isFuture());

            if ($passed->isNotEmpty()) {
                return ['status' => 'passed', 'exam_id' => $examId];
            }

            $assigned = collect($exams->assignments)
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
}