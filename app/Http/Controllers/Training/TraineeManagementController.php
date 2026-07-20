<?php

namespace App\Http\Controllers\Training;

use App\Domain\Training\Actions\AddTraineeToCourse;
use App\Domain\Training\Actions\AssignTrainee;
use App\Domain\Training\Actions\ClaimTrainee;
use App\Domain\Training\Actions\FinishCourse;
use App\Domain\Training\Actions\ReactivateTrainee;
use App\Domain\Training\Actions\RemoveTrainee;
use App\Domain\Training\Actions\UnclaimTrainee;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TraineeManagementController extends Controller
{
    public function __construct(
        private ClaimTrainee $claimTrainee,
        private UnclaimTrainee $unclaimTrainee,
        private AssignTrainee $assignTrainee,
        private RemoveTrainee $removeTrainee,
        private ReactivateTrainee $reactivateTrainee,
        private AddTraineeToCourse $addTraineeToCourse,
        private FinishCourse $finishCourse,
    ) {}

    public function claimTrainee(Request $request)
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $trainee = User::findOrFail($validated['trainee_id']);

        if (! $user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot claim trainees for this course']);
        }

        if (! $course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
            return back()->withErrors(['error' => 'Trainee is not in this course']);
        }

        try {
            $this->claimTrainee->execute($course, $trainee, $user);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (\Exception $e) {
            Log::error('Error claiming trainee', ['mentor_id' => $user->id, 'trainee_id' => $trainee->id, 'course_id' => $course->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'An error occurred while claiming the trainee.']);
        }
    }

    public function unclaimTrainee(Request $request)
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $trainee = User::findOrFail($validated['trainee_id']);

        if (! $user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot unclaim trainees for this course']);
        }

        if (! $course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
            return back()->withErrors(['error' => 'Trainee is not in this course']);
        }

        try {
            $this->unclaimTrainee->execute($course, $trainee, $user);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (\Exception $e) {
            Log::error('Error unclaiming trainee', ['mentor_id' => $user->id, 'trainee_id' => $trainee->id, 'course_id' => $course->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'An error occurred while unclaiming the trainee.']);
        }
    }

    public function assignTrainee(Request $request)
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
            'mentor_id' => 'required|integer|exists:users,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $trainee = User::findOrFail($validated['trainee_id']);
        $newMentor = User::findOrFail($validated['mentor_id']);

        if (! $user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot assign trainees for this course']);
        }

        if (! $newMentor->is_superuser && ! $newMentor->is_admin && ! $newMentor->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return back()->withErrors(['error' => 'Selected mentor cannot mentor this course']);
        }

        if (! $course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
            return back()->withErrors(['error' => 'Trainee is not in this course']);
        }

        try {
            $this->assignTrainee->execute($course, $trainee, $newMentor, $user);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (\Exception $e) {
            Log::error('Error assigning trainee', ['mentor_id' => $user->id, 'trainee_id' => $trainee->id, 'course_id' => $course->id, 'new_mentor_id' => $validated['mentor_id'], 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'An error occurred while assigning the trainee.']);
        }
    }

    public function removeTrainee(Request $request)
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $trainee = User::findOrFail($validated['trainee_id']);

        if (! $user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        try {
            $this->removeTrainee->execute($course, $trainee, $user);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (\Exception $e) {
            Log::error('Error removing trainee from course', ['mentor_id' => $user->id, 'trainee_id' => $trainee->id, 'course_id' => $course->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'An error occurred while removing the trainee.']);
        }
    }

    public function reactivateTrainee(Request $request)
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $trainee = User::findOrFail($validated['trainee_id']);

        if (! $user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        if (! $course->completedTrainees()->where('user_id', $trainee->id)->exists()) {
            return back()->withErrors(['error' => 'Trainee has not completed this course']);
        }

        try {
            $this->reactivateTrainee->execute($course, $trainee, $user);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (\Exception $e) {
            Log::error('Error reactivating trainee', ['mentor_id' => $user->id, 'trainee_id' => $trainee->id, 'course_id' => $course->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'An error occurred while reactivating the trainee.']);
        }
    }

    public function addTraineeToCourse(Request $request)
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $trainee = User::findOrFail($validated['user_id']);

        if (! $user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        if ($course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
            return back()->withErrors(['error' => 'This trainee is already active in this course']);
        }

        if (! $trainee->isVatsimUser()) {
            return back()->withErrors(['error' => 'This user does not have a VATSIM account']);
        }

        try {
            $this->addTraineeToCourse->execute($course, $trainee, $user);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (\Exception $e) {
            Log::error('Error adding trainee to course', ['mentor_id' => $user->id, 'trainee_id' => $trainee->id, 'course_id' => $course->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'An error occurred while adding the trainee.']);
        }
    }

    public function finishCourse(Request $request)
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $trainee = User::findOrFail($validated['trainee_id']);

        if (! $user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        if (! $course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
            return back()->withErrors(['error' => 'Trainee is not in this course']);
        }

        try {
            $this->finishCourse->execute($course, $trainee, $user);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (\Exception $e) {
            Log::error('Error finishing course', ['mentor_id' => $user->id, 'trainee_id' => $trainee->id, 'course_id' => $course->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return back()->withErrors(['error' => 'An error occurred while finishing the course. Please try again.']);
        }
    }
}
