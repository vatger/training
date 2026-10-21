<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Subfamiliarisation;
use App\Models\SubfamiliarisationSession;
use App\Models\User;
use App\Services\SubfamiliarisationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubfamiliarisationSessionController extends Controller
{
    public function __construct(
        private SubfamiliarisationService $subfamiliarisationService,
    ) {}

    public function create(Request $request, int $traineeId, int $courseId): Response|RedirectResponse
    {
        $user = $request->user();
        $trainee = User::findOrFail($traineeId);
        $course = Course::findOrFail($courseId);

        if (! $user->isMentorForCourse($course)) {
            abort(403, 'You are not a mentor for this course.');
        }

        $subfamiliarisations = $this->subfamiliarisationService->relevantSubfamiliarisationsForCourse($course);

        if ($subfamiliarisations->isEmpty()) {
            return redirect()->route('overview.index')
                ->withErrors(['error' => 'This course is not tied to any subfamiliarisation.']);
        }

        return Inertia::render('training/subfamiliarisations/create', [
            'trainee' => ['id' => $trainee->id, 'name' => $trainee->name, 'vatsim_id' => $trainee->vatsim_id],
            'course' => ['id' => $course->id, 'name' => $course->name],
            'subfamiliarisations' => $subfamiliarisations->map(fn (Subfamiliarisation $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'moodleComplete' => $this->subfamiliarisationService->hasCompletedMoodleRequirements($trainee, $s),
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'subfamiliarisation_id' => 'required|integer|exists:subfamiliarisations,id',
            'user_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
            'completed_at' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $subfamiliarisation = Subfamiliarisation::findOrFail($validated['subfamiliarisation_id']);
        $trainee = User::findOrFail($validated['user_id']);

        if (! $user->isMentorForCourse($course)) {
            abort(403, 'You are not a mentor for this course.');
        }

        $relevant = $this->subfamiliarisationService->relevantSubfamiliarisationsForCourse($course);

        if (! $relevant->contains('id', $subfamiliarisation->id)) {
            abort(403, 'This course is not tied to the selected subfamiliarisation.');
        }

        if (! $this->subfamiliarisationService->hasCompletedMoodleRequirements($trainee, $subfamiliarisation)) {
            throw ValidationException::withMessages([
                'subfamiliarisation_id' => 'This trainee has not completed all required Moodle courses for this subfamiliarisation yet.',
            ]);
        }

        SubfamiliarisationSession::updateOrCreate(
            [
                'subfamiliarisation_id' => $subfamiliarisation->id,
                'user_id' => $validated['user_id'],
            ],
            [
                'mentor_id' => $user->id,
                'completed_at' => $validated['completed_at'],
                'remarks' => $validated['remarks'] ?? null,
            ]
        );

        return redirect()->route('overview.index', ['last_course_id' => $course->id])
            ->with('success', 'Subfamiliarisation session logged successfully.');
    }
}
