<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TraineeOrderController extends Controller
{
    /**
     * Update the order of trainees for a specific course and mentor
     */
    public function updateOrder(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'trainee_ids' => 'required|array',
            'trainee_ids.*' => 'integer|exists:users,id',
        ]);

        $courseId = $request->course_id;
        $traineeIds = $request->trainee_ids;

        // Check if user can mentor this course
        $course = \App\Models\Course::findOrFail($courseId);
        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'You cannot modify this course'], 403);
        }

        try {
            DB::transaction(function () use ($courseId, $traineeIds, $user) {
                // Update the order for each trainee
                foreach ($traineeIds as $index => $traineeId) {
                    DB::table('course_trainees')
                        ->where('course_id', $courseId)
                        ->where('user_id', $traineeId)
                        ->update([
                            'custom_order' => $index + 1,
                            'custom_order_mentor_id' => $user->id,
                            'updated_at' => now(),
                        ]);
                }
            });

            Log::info('Trainee order updated', [
                'mentor_id' => $user->id,
                'course_id' => $courseId,
                'trainee_count' => count($traineeIds),
            ]);

            return back()->with('success', 'Trainee order updated successfully');

        } catch (\Exception $e) {
            Log::error('Error updating trainee order', [
                'mentor_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to update trainee order']);
        }
    }

    /**
     * Reset the custom order for a course (back to default)
     */
    public function resetOrder(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $courseId = $request->course_id;

        // Check if user can mentor this course
        $course = \App\Models\Course::findOrFail($courseId);
        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'You cannot modify this course'], 403);
        }

        try {
            DB::table('course_trainees')
                ->where('course_id', $courseId)
                ->where('custom_order_mentor_id', $user->id)
                ->update([
                    'custom_order' => null,
                    'custom_order_mentor_id' => null,
                    'updated_at' => now(),
                ]);

            Log::info('Trainee order reset', [
                'mentor_id' => $user->id,
                'course_id' => $courseId,
            ]);

            return back()->with('success', 'Trainee order reset successfully');

        } catch (\Exception $e) {
            Log::error('Error resetting trainee order', [
                'mentor_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to reset trainee order']);
        }
    }
}