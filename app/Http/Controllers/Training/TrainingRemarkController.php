<?php

namespace App\Http\Controllers\Training;

use App\Domain\Training\Actions\UpdateTraineeRemark;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrainingRemarkController extends Controller
{
    public function __construct(
        private UpdateTraineeRemark $updateTraineeRemark,
    ) {}

    public function updateRemark(Request $request)
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
            'remark' => 'nullable|string|max:1000',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $trainee = User::findOrFail($validated['trainee_id']);

        if (! $user->canViewCourse($course)) {
            return back()->withErrors(['error' => 'You cannot modify this course']);
        }

        try {
            $this->updateTraineeRemark->execute($course, $trainee, $user, $validated['remark'] ?? '');

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (\Exception $e) {
            Log::error('Error updating trainee remark', ['mentor_id' => $user->id, 'trainee_id' => $validated['trainee_id'], 'course_id' => $validated['course_id'], 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'An error occurred while updating the remark.']);
        }
    }
}
