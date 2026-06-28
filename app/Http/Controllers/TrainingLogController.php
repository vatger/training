<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrainingLogRequest;
use App\Models\Course;
use App\Models\TrainingLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingLogController extends Controller
{
    private function categories(): array
    {
        return [
            ['name' => 'theory',                      'label' => 'Theory',                        'description' => 'Understanding of theoretical concepts, rules, and procedures.'],
            ['name' => 'phraseology',                 'label' => 'Phraseology',                   'description' => 'Correct and standardised use of radio telephony language.'],
            ['name' => 'coordination',                'label' => 'Coordination',                  'description' => 'Effective coordination with adjacent sectors and units.'],
            ['name' => 'tag_management',              'label' => 'Tag Management',                'description' => 'Proper management of radar tags, labels, and display settings.'],
            ['name' => 'situational_awareness',       'label' => 'Situational Awareness',         'description' => 'Maintaining a clear mental picture of all traffic and potential conflicts.'],
            ['name' => 'problem_recognition',         'label' => 'Problem Recognition',           'description' => 'Timely identification of developing problems or conflicts.'],
            ['name' => 'traffic_planning',            'label' => 'Traffic Planning',              'description' => 'Planning traffic flow to avoid conflicts and optimise capacity.'],
            ['name' => 'reaction',                    'label' => 'Reaction',                      'description' => 'Timeliness and quality of responses to developing situations.'],
            ['name' => 'separation',                  'label' => 'Separation',                    'description' => 'Maintaining correct separation standards between all aircraft.'],
            ['name' => 'efficiency',                  'label' => 'Efficiency',                    'description' => 'Effective use of airspace and minimising unnecessary instructions.'],
            ['name' => 'ability_to_work_under_pressure', 'label' => 'Work Under Pressure',        'description' => 'Performance and composure during high-workload situations.'],
            ['name' => 'motivation',                  'label' => 'Motivation',                    'description' => 'Engagement, enthusiasm, and willingness to learn and improve.'],
        ];
    }

    private function sessionTypes(): array
    {
        return [
            ['value' => 'O', 'label' => 'Online'],
            ['value' => 'S', 'label' => 'Sim'],
            ['value' => 'L', 'label' => 'Lesson'],
            ['value' => 'C', 'label' => 'Custom'],
        ];
    }

    private function ratingOptions(): array
    {
        return [
            ['value' => 0, 'label' => 'Not Rated'],
            ['value' => 1, 'label' => 'Requirements Not Met'],
            ['value' => 2, 'label' => 'Requirements Partially Met'],
            ['value' => 3, 'label' => 'Requirements Met'],
            ['value' => 4, 'label' => 'Requirements Exceeded'],
        ];
    }

    private function trafficLevels(): array
    {
        return [
            ['value' => 'L', 'label' => 'Low'],
            ['value' => 'M', 'label' => 'Medium'],
            ['value' => 'H', 'label' => 'High'],
        ];
    }

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('overview.index');
    }

    public function create(Request $request, int $traineeId, int $courseId): Response|RedirectResponse
    {
        $user    = $request->user();
        $trainee = User::findOrFail($traineeId);
        $course  = Course::findOrFail($courseId);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            abort(403, 'You are not a mentor for this course.');
        }

        if (!$course->activeTrainees()->where('user_id', $trainee->id)->exists()) {
            return redirect()->route('overview.index')->withErrors(['error' => 'This trainee is not active in the selected course.']);
        }

        return Inertia::render('training/logs/create', [
            'trainee'       => ['id' => $trainee->id, 'name' => $trainee->name, 'vatsim_id' => $trainee->vatsim_id],
            'course'        => ['id' => $course->id, 'name' => $course->name, 'position' => $course->position, 'type' => $course->type],
            'categories'    => $this->categories(),
            'sessionTypes'  => $this->sessionTypes(),
            'ratingOptions' => $this->ratingOptions(),
            'trafficLevels' => $this->trafficLevels(),
            'continueDraft' => $request->query('continue') === '1',
            'isEditing'     => false,
        ]);
    }

    public function store(TrainingLogRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['mentor_id'] = $request->user()->id;

        $log = TrainingLog::create($data);

        return redirect()->route('training-logs.show', $log->id)
            ->with('success', 'Training log created successfully.');
    }

    public function show(Request $request, TrainingLog $trainingLog): Response
    {
        $user = $request->user();

        if (!$user->can('view', $trainingLog)) {
            abort(403);
        }

        $trainingLog->load(['trainee', 'mentor', 'course']);

        $canViewInternal = $user->can('viewInternal', $trainingLog);

        return Inertia::render('training/logs/view', [
            'log'            => $this->formatLogForView($trainingLog, $canViewInternal),
            'canEdit'        => $user->can('update', $trainingLog),
            'canViewInternal' => $canViewInternal,
            'categories'     => $this->categories(),
        ]);
    }

    public function edit(Request $request, TrainingLog $trainingLog): Response
    {
        $user = $request->user();

        if (!$user->can('update', $trainingLog)) {
            abort(403);
        }

        $trainingLog->load(['trainee', 'mentor', 'course']);

        return Inertia::render('training/logs/create', [
            'log'           => $this->formatLogForEdit($trainingLog),
            'trainee'       => ['id' => $trainingLog->trainee->id, 'name' => $trainingLog->trainee->name, 'vatsim_id' => $trainingLog->trainee->vatsim_id],
            'course'        => $trainingLog->course ? ['id' => $trainingLog->course->id, 'name' => $trainingLog->course->name, 'position' => $trainingLog->course->position, 'type' => $trainingLog->course->type] : null,
            'categories'    => $this->categories(),
            'sessionTypes'  => $this->sessionTypes(),
            'ratingOptions' => $this->ratingOptions(),
            'trafficLevels' => $this->trafficLevels(),
            'continueDraft' => false,
            'isEditing'     => true,
        ]);
    }

    public function update(TrainingLogRequest $request, TrainingLog $trainingLog): RedirectResponse
    {
        $user = $request->user();

        if (!$user->can('update', $trainingLog)) {
            abort(403);
        }

        $trainingLog->update($request->validated());

        return redirect()->route('training-logs.show', $trainingLog->id)
            ->with('success', 'Training log updated successfully.');
    }

    public function destroy(Request $request, TrainingLog $trainingLog): RedirectResponse
    {
        $user = $request->user();

        if (!$user->can('delete', $trainingLog)) {
            abort(403);
        }

        $trainingLog->delete();

        return redirect()->route('overview.index')
            ->with('success', 'Training log deleted successfully.');
    }

    private function formatLogForView(TrainingLog $log, bool $canViewInternal): array
    {
        return [
            'id'                        => $log->id,
            'session_date'              => $log->session_date->format('Y-m-d'),
            'position'                  => $log->position,
            'type'                      => $log->type,
            'type_display'              => $log->type_display,
            'traffic_level'             => $log->traffic_level,
            'traffic_level_display'     => $log->traffic_level_display,
            'traffic_complexity'        => $log->traffic_complexity,
            'traffic_complexity_display' => $log->traffic_complexity_display,
            'runway_configuration'      => $log->runway_configuration,
            'surrounding_stations'      => $log->surrounding_stations,
            'session_duration'          => $log->session_duration,
            'special_procedures'        => $log->special_procedures,
            'airspace_restrictions'     => $log->airspace_restrictions,
            'trainee'                   => ['id' => $log->trainee->id, 'name' => $log->trainee->name, 'vatsim_id' => $log->trainee->vatsim_id],
            'mentor'                    => ['id' => $log->mentor->id, 'name' => $log->mentor->name, 'vatsim_id' => $log->mentor->vatsim_id],
            'course'                    => $log->course ? ['id' => $log->course->id, 'name' => $log->course->name, 'position' => $log->course->position, 'type' => $log->course->type] : null,
            'evaluations'               => $log->getEvaluationCategories(),
            'final_comment'             => $log->final_comment,
            'internal_remarks'          => $canViewInternal ? $log->internal_remarks : null,
            'result'                    => $log->result,
            'next_step'                 => $log->next_step,
            'average_rating'            => $log->average_rating,
            'has_ratings'               => $log->hasRatings(),
            'created_at'                => $log->created_at->toIso8601String(),
            'updated_at'                => $log->updated_at->toIso8601String(),
        ];
    }

    private function formatLogForEdit(TrainingLog $log): array
    {
        return [
            'id'                        => $log->id,
            'session_date'              => $log->session_date->format('Y-m-d'),
            'position'                  => $log->position,
            'type'                      => $log->type,
            'traffic_level'             => $log->traffic_level,
            'traffic_complexity'        => $log->traffic_complexity,
            'runway_configuration'      => $log->runway_configuration,
            'surrounding_stations'      => $log->surrounding_stations,
            'session_duration'          => $log->session_duration,
            'special_procedures'        => $log->special_procedures,
            'airspace_restrictions'     => $log->airspace_restrictions,
            'evaluations'               => $log->getEvaluationCategories(),
            'internal_remarks'          => $log->internal_remarks,
            'final_comment'             => $log->final_comment,
            'result'                    => $log->result,
            'next_step'                 => $log->next_step,
        ];
    }
}
