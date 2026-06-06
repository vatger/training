<?php

namespace App\Http\Controllers\Training;

use App\Domain\Training\Actions\AddMentorToCourse;
use App\Domain\Training\Actions\RemoveMentorFromCourse;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MentorManagementController extends Controller
{
    public function __construct(
        private AddMentorToCourse      $addMentorToCourse,
        private RemoveMentorFromCourse $removeMentorFromCourse,
    ) {}

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