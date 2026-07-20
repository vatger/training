<?php

namespace App\Http\Controllers\Cpt;

use App\Domain\Cpt\Actions\CreateCpt;
use App\Domain\Cpt\Events\CptDeleted;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Cpt;
use App\Models\Examiner;
use App\Models\User;
use App\Services\CptNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CptController extends Controller
{
    public function __construct(
        private readonly CreateCpt $createCpt,
        private readonly CptNotificationService $notifications,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $user->load('examiner.user');

        $cpts = Cpt::with(['trainee', 'examiner.examiner', 'local', 'course.mentors'])
            ->pending()
            ->orderBy('date')
            ->get()
            ->map(fn ($cpt) => [
                'id' => $cpt->id,
                'trainee' => [
                    'id' => $cpt->trainee->id,
                    'name' => $cpt->trainee->full_name,
                    'vatsim_id' => $cpt->trainee->vatsim_id,
                ],
                'examiner' => $cpt->examiner ? [
                    'id' => $cpt->examiner->id,
                    'name' => $cpt->examiner->full_name,
                    'is_current_user' => $cpt->examiner_id === $user->id,
                ] : null,
                'local' => $cpt->local ? [
                    'id' => $cpt->local->id,
                    'name' => $cpt->local->full_name,
                    'is_current_user' => $cpt->local_id === $user->id,
                ] : null,
                'course' => [
                    'id' => $cpt->course->id,
                    'name' => $cpt->course->name,
                    'solo_station' => $cpt->course->solo_station,
                    'position' => $cpt->course->position,
                ],
                'date' => $cpt->date->toIso8601String(),
                'date_formatted' => $cpt->date->format('d M Y'),
                'time_formatted' => $cpt->date->format('H:i'),
                'confirmed' => $cpt->confirmed,
                'log_uploaded' => $cpt->log_uploaded,
                'can_delete' => $user->isSuperuser() || $cpt->course->mentors->contains($user->id),
                'can_view_upload' => $user->isSuperuser() || $user->isLeadership() || $cpt->examiner_id === $user->id || $cpt->local_id === $user->id,
                'can_upload' => $user->isSuperuser() || $cpt->examiner_id === $user->id || $cpt->local_id === $user->id,
                'can_join_examiner' => $this->canJoinAsExaminer($user, $cpt),
                'can_join_local' => $this->canJoinAsLocal($user, $cpt),
            ]);

        return Inertia::render('cpt/management', [
            'cpts' => $cpts,
            'statistics' => [
                'total_cpts' => $cpts->count(),
                'confirmed_cpts' => $cpts->where('confirmed', true)->count(),
                'pending_cpts' => $cpts->where('confirmed', false)->count(),
            ],
            'cpt_templates' => $this->getCptTemplates(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $courses = $user->isSuperuser()
            ? Course::where('type', 'RTG')->orderBy('name')->get()
            : $user->mentorCourses()->where('type', 'RTG')->orderBy('name')->get();

        return Inertia::render('cpt/create', [
            'courses' => $courses->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'solo_station' => $c->solo_station,
                'position' => $c->position,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'trainee_id' => 'required|exists:users,id',
            'date' => 'required|date|after:now',
            'examiner_id' => 'nullable|exists:users,id',
            'local_id' => 'nullable|exists:users,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $trainee = User::findOrFail($validated['trainee_id']);

        if ($validated['trainee_id'] === ($validated['examiner_id'] ?? null)) {
            return back()->withErrors(['examiner_id' => 'Trainee cannot be the same as examiner.']);
        }

        if ($validated['trainee_id'] === ($validated['local_id'] ?? null)) {
            return back()->withErrors(['local_id' => 'Trainee cannot be the same as local contact.']);
        }

        if (isset($validated['examiner_id'], $validated['local_id'])
            && $validated['examiner_id'] === $validated['local_id']) {
            return back()->withErrors(['local_id' => 'Examiner cannot be the same as local contact.']);
        }

        $examiner = null;
        if (isset($validated['examiner_id'])) {
            $examinerUser = User::with('examiner')->findOrFail($validated['examiner_id']);

            if (! $examinerUser->examiner) {
                return back()->withErrors(['examiner_id' => 'Selected user is not an examiner.']);
            }

            if (! $examinerUser->examiner->hasPosition($course->position)) {
                return back()->withErrors(['examiner_id' => 'Selected examiner is not authorized for this position.']);
            }

            $date = Carbon::parse($validated['date']);
            if ($course->mentors->contains($examinerUser->id) && $date->diffInHours(now()) > 36) {
                return back()->withErrors(['examiner_id' => 'Course mentors cannot be examiners more than 36 hours in advance.']);
            }

            $examiner = $examinerUser;
        }

        $local = null;
        if (isset($validated['local_id'])) {
            if (! $course->mentors->contains($validated['local_id'])) {
                return back()->withErrors(['local_id' => 'Selected user is not a mentor for this course.']);
            }
            $local = User::findOrFail($validated['local_id']);
        }

        $this->createCpt->execute(
            course: $course,
            trainee: $trainee,
            date: $validated['date'],
            creator: $request->user(),
            examiner: $examiner,
            local: $local,
        );

        return redirect()->route('cpt.index')->with('success', 'CPT created successfully.');
    }

    public function destroy(Request $request, Cpt $cpt)
    {
        $user = $request->user();
        $cpt->load('course.mentors', 'trainee');

        if (! $user->isSuperuser() && ! $cpt->course->mentors->contains($user->id)) {
            return back()->withErrors(['error' => 'You do not have permission to delete this CPT.']);
        }

        if ($cpt->passed !== null) {
            return back()->withErrors(['error' => 'Cannot delete a graded CPT.']);
        }

        $wasConfirmed = $cpt->confirmed;

        event(new CptDeleted($cpt, $cpt->course, $cpt->trainee, $user));

        $cpt->delete();

        if ($wasConfirmed) {
            $this->notifications->broadcastConfirmedCpts();
        }

        return back()->with('success', 'CPT deleted successfully.');
    }

    public function getCourseData(Request $request)
    {
        $courseId = $request->get('course_id');

        if (! $courseId) {
            return response()->json(['examiners' => [], 'mentors' => [], 'trainees' => []]);
        }

        $course = Course::with(['activeTrainees', 'mentors'])->findOrFail($courseId);
        $date = Carbon::parse($request->get('date'));

        $examinersQuery = Examiner::with('user')
            ->whereJsonContains('positions', $course->position);

        if ($date->diffInHours(now()) > 36) {
            $examinersQuery->whereNotIn('user_id', $course->mentors->pluck('id')->toArray());
        }

        return response()->json([
            'examiners' => $examinersQuery->get()->map(fn ($e) => [
                'id' => $e->user->id,
                'name' => $e->full_display,
            ]),
            'mentors' => $course->mentors->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->full_name,
            ])->values(),
            'trainees' => $course->activeTrainees->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->full_name,
                'vatsim_id' => $t->vatsim_id,
            ])->values(),
        ]);
    }

    private function getCptTemplates(): array
    {
        $templatesPath = storage_path('app/public/cpt-templates');

        if (! file_exists($templatesPath)) {
            return [];
        }

        $templates = collect(Storage::disk('public')->files('cpt-templates'))
            ->filter(fn ($f) => pathinfo($f, PATHINFO_EXTENSION) === 'pdf')
            ->map(fn ($f) => [
                'name' => pathinfo(basename($f), PATHINFO_FILENAME),
                'filename' => basename($f),
            ])
            ->sortBy('name')
            ->values()
            ->toArray();

        return $templates;
    }

    private function canJoinAsExaminer(User $user, Cpt $cpt): bool
    {
        if ($cpt->examiner_id === $user->id || $cpt->local_id === $user->id) {
            return false;
        }

        if (! $user->relationLoaded('examiner')) {
            $user->load('examiner');
        }

        if (! $user->examiner || ! $user->examiner->hasPosition($cpt->course->position)) {
            return false;
        }

        if (! $cpt->course->relationLoaded('mentors')) {
            $cpt->course->load('mentors');
        }

        if ($cpt->course->mentors->contains($user->id) && $cpt->date->diffInHours(now()) > 36) {
            return false;
        }

        return true;
    }

    private function canJoinAsLocal(User $user, Cpt $cpt): bool
    {
        if ($cpt->local_id === $user->id || $cpt->examiner_id === $user->id) {
            return false;
        }

        if (! $cpt->course->relationLoaded('mentors')) {
            $cpt->course->load('mentors');
        }

        return $cpt->course->mentors->contains($user->id);
    }
}
