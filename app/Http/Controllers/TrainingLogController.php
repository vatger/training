<?php

namespace App\Http\Controllers;

use App\Models\TrainingLog;
use App\Models\User;
use App\Models\Course;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TrainingLogController extends Controller
{
    /**
     * Display a listing of training logs for the authenticated user
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->is_superuser || $user->is_admin) {
            $logs = TrainingLog::with(['trainee', 'mentor', 'course'])
                ->recent()
                ->paginate(20);
        } elseif ($user->isMentor()) {
            $logs = TrainingLog::with(['trainee', 'mentor', 'course'])
                ->where(function ($query) use ($user) {
                    $query->where('mentor_id', $user->id)
                        ->orWhereHas('course', function ($q) use ($user) {
                            $q->whereHas('mentors', function ($mq) use ($user) {
                                $mq->where('user_id', $user->id);
                            });
                        });
                })
                ->recent()
                ->paginate(20);
        } else {
            $logs = TrainingLog::with(['trainee', 'mentor', 'course'])
                ->forTrainee($user->id)
                ->recent()
                ->paginate(20);
        }

        return Inertia::render('training/logs/index', [
            'logs' => $logs->map(function ($log) {
                return $this->formatLogForFrontend($log);
            }),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new training log
     */
    public function create(Request $request, int $traineeId, int $courseId): Response
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            abort(403, 'You do not have permission to create training logs.');
        }

        $trainee = User::findOrFail($traineeId);
        $course = Course::with(['mentorGroup'])->findOrFail($courseId);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            abort(403, 'You are not a mentor for this course.');
        }

        $categories = $this->getEvaluationCategories();

        $continueDraft = $request->query('continue') === '1';

        return Inertia::render('training/logs/create', [
            'trainee' => [
                'id' => $trainee->id,
                'name' => $trainee->name,
                'vatsim_id' => $trainee->vatsim_id,
            ],
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'position' => $course->position,
                'type' => $course->type,
            ],
            'categories' => $categories,
            'sessionTypes' => $this->getSessionTypes(),
            'ratingOptions' => $this->getRatingOptions(),
            'trafficLevels' => $this->getTrafficLevels(),
            'continueDraft' => $continueDraft,
        ]);
    }

    /**
     * Store a newly created training log in storage
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->withErrors(['error' => 'You do not have permission to create training logs.']);
        }

        $validated = $request->validate([
            'trainee_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
            'session_date' => 'required|date',
            'position' => 'required|string|max:25',
            'type' => 'required|string|in:O,S,L,C',
            
            'traffic_level' => 'nullable|string|in:L,M,H',
            'traffic_complexity' => 'nullable|string|in:L,M,H',
            'runway_configuration' => 'nullable|string|max:50',
            'surrounding_stations' => 'nullable|string',
            'session_duration' => 'nullable|integer|min:1',
            'special_procedures' => 'nullable|string',
            'airspace_restrictions' => 'nullable|string',
            
            'theory' => 'required|integer|min:0|max:4',
            'theory_positives' => 'nullable|string',
            'theory_negatives' => 'nullable|string',
            
            'phraseology' => 'required|integer|min:0|max:4',
            'phraseology_positives' => 'nullable|string',
            'phraseology_negatives' => 'nullable|string',
            
            'coordination' => 'required|integer|min:0|max:4',
            'coordination_positives' => 'nullable|string',
            'coordination_negatives' => 'nullable|string',
            
            'tag_management' => 'required|integer|min:0|max:4',
            'tag_management_positives' => 'nullable|string',
            'tag_management_negatives' => 'nullable|string',
            
            'situational_awareness' => 'required|integer|min:0|max:4',
            'situational_awareness_positives' => 'nullable|string',
            'situational_awareness_negatives' => 'nullable|string',
            
            'problem_recognition' => 'required|integer|min:0|max:4',
            'problem_recognition_positives' => 'nullable|string',
            'problem_recognition_negatives' => 'nullable|string',
            
            'traffic_planning' => 'required|integer|min:0|max:4',
            'traffic_planning_positives' => 'nullable|string',
            'traffic_planning_negatives' => 'nullable|string',
            
            'reaction' => 'required|integer|min:0|max:4',
            'reaction_positives' => 'nullable|string',
            'reaction_negatives' => 'nullable|string',
            
            'separation' => 'required|integer|min:0|max:4',
            'separation_positives' => 'nullable|string',
            'separation_negatives' => 'nullable|string',
            
            'efficiency' => 'required|integer|min:0|max:4',
            'efficiency_positives' => 'nullable|string',
            'efficiency_negatives' => 'nullable|string',
            
            'ability_to_work_under_pressure' => 'required|integer|min:0|max:4',
            'ability_to_work_under_pressure_positives' => 'nullable|string',
            'ability_to_work_under_pressure_negatives' => 'nullable|string',
            
            'motivation' => 'required|integer|min:0|max:4',
            'motivation_positives' => 'nullable|string',
            'motivation_negatives' => 'nullable|string',
            
            'internal_remarks' => 'nullable|string',
            'final_comment' => 'nullable|string',
            'result' => 'required|boolean',
            'next_step' => 'nullable|string',
        ]);

        $course = Course::findOrFail($validated['course_id']);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return back()->withErrors(['error' => 'You are not a mentor for this course.']);
        }

        try {
            $log = TrainingLog::create([
                ...$validated,
                'mentor_id' => $user->id,
            ]);

            $trainee = User::findOrFail($validated['trainee_id']);

            ActivityLogger::log(
                'traininglog.added',
                $log,
                "{$user->name} created training log for {$trainee->name}",
                [
                    'trainee_id' => $validated['trainee_id'],
                    'trainee_name' => $trainee->name,
                    'mentor_id' => $user->id,
                    'mentor_name' => $user->name,
                    'course_id' => $validated['course_id'],
                    'course_name' => $course->name,
                    'session_date' => $validated['session_date'],
                    'position' => $validated['position'],
                    'type' => $validated['type'],
                    'result' => $validated['result'] ? 'Pass' : 'Fail',
                ],
                $user->id
            );

            Log::info('Training log created', [
                'log_id' => $log->id,
                'mentor_id' => $user->id,
                'trainee_id' => $validated['trainee_id'],
                'course_id' => $validated['course_id'],
            ]);

            return redirect()
                ->route('training-logs.show', $log->id)
                ->with('success', 'Training log created successfully.');

        } catch (\Exception $e) {
            Log::error('Error creating training log', [
                'mentor_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the training log.']);
        }
    }

    /**
     * Display the specified training log
     */
    public function show(Request $request, int $id): Response
    {
        $user = $request->user();
        $log = TrainingLog::with(['trainee', 'mentor', 'course'])->findOrFail($id);

        $isOwnLog = $user->id === $log->trainee_id;
        $isLogMentor = $user->id === $log->mentor_id;
        $isCourseMentor = $log->course && $user->mentorCourses()->where('courses.id', $log->course_id)->exists();
        $isAdmin = $user->is_superuser || $user->is_admin;

        if (!$isOwnLog && !$isLogMentor && !$isCourseMentor && !$isAdmin) {
            abort(403, 'You do not have permission to view this log.');
        }

        $canViewInternal = $isLogMentor || $isCourseMentor || $isAdmin;
        $canEdit = ($isLogMentor || $isAdmin);

        return Inertia::render('training/logs/view', [
            'log' => $this->formatLogForFrontend($log, $canViewInternal),
            'canEdit' => $canEdit,
            'canViewInternal' => $canViewInternal,
            'categories' => $this->getEvaluationCategories(),
        ]);
    }

    public function edit(Request $request, int $id): Response
    {
        $user = $request->user();
        $log = TrainingLog::with(['trainee', 'mentor', 'course'])->findOrFail($id);

        if ($user->id !== $log->mentor_id && !$user->is_superuser && !$user->is_admin) {
            abort(403, 'You do not have permission to edit this log.');
        }

        if ($log->course && !$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $log->course_id)->exists()) {
            abort(403, 'You are no longer a mentor for this course.');
        }

        $categories = $this->getEvaluationCategories();

        return Inertia::render('training/logs/create', [
            'log' => $this->formatLogForFrontend($log, true),
            'trainee' => [
                'id' => $log->trainee->id,
                'name' => $log->trainee->name,
                'vatsim_id' => $log->trainee->vatsim_id,
            ],
            'course' => $log->course ? [
                'id' => $log->course->id,
                'name' => $log->course->name,
                'position' => $log->course->position,
                'type' => $log->course->type,
            ] : null,
            'categories' => $categories,
            'sessionTypes' => $this->getSessionTypes(),
            'ratingOptions' => $this->getRatingOptions(),
            'trafficLevels' => $this->getTrafficLevels(),
            'continueDraft' => false,
            'isEditing' => true,
        ]);
    }

    /**
     * Update the specified training log in storage
     */
    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $log = TrainingLog::findOrFail($id);

        if ($user->id !== $log->mentor_id && !$user->is_superuser) {
            return back()->withErrors(['error' => 'You do not have permission to edit this log.']);
        }

        $validated = $request->validate([
            'session_date' => 'required|date',
            'position' => 'required|string|max:25',
            'type' => 'required|string|in:O,S,L,C',
            
            'traffic_level' => 'nullable|string|in:L,M,H',
            'traffic_complexity' => 'nullable|string|in:L,M,H',
            'runway_configuration' => 'nullable|string|max:50',
            'surrounding_stations' => 'nullable|string',
            'session_duration' => 'nullable|integer|min:1',
            'special_procedures' => 'nullable|string',
            'airspace_restrictions' => 'nullable|string',
            
            'theory' => 'required|integer|min:0|max:4',
            'theory_positives' => 'nullable|string',
            'theory_negatives' => 'nullable|string',
            
            'phraseology' => 'required|integer|min:0|max:4',
            'phraseology_positives' => 'nullable|string',
            'phraseology_negatives' => 'nullable|string',
            
            'coordination' => 'required|integer|min:0|max:4',
            'coordination_positives' => 'nullable|string',
            'coordination_negatives' => 'nullable|string',
            
            'tag_management' => 'required|integer|min:0|max:4',
            'tag_management_positives' => 'nullable|string',
            'tag_management_negatives' => 'nullable|string',
            
            'situational_awareness' => 'required|integer|min:0|max:4',
            'situational_awareness_positives' => 'nullable|string',
            'situational_awareness_negatives' => 'nullable|string',
            
            'problem_recognition' => 'required|integer|min:0|max:4',
            'problem_recognition_positives' => 'nullable|string',
            'problem_recognition_negatives' => 'nullable|string',
            
            'traffic_planning' => 'required|integer|min:0|max:4',
            'traffic_planning_positives' => 'nullable|string',
            'traffic_planning_negatives' => 'nullable|string',
            
            'reaction' => 'required|integer|min:0|max:4',
            'reaction_positives' => 'nullable|string',
            'reaction_negatives' => 'nullable|string',
            
            'separation' => 'required|integer|min:0|max:4',
            'separation_positives' => 'nullable|string',
            'separation_negatives' => 'nullable|string',
            
            'efficiency' => 'required|integer|min:0|max:4',
            'efficiency_positives' => 'nullable|string',
            'efficiency_negatives' => 'nullable|string',
            
            'ability_to_work_under_pressure' => 'required|integer|min:0|max:4',
            'ability_to_work_under_pressure_positives' => 'nullable|string',
            'ability_to_work_under_pressure_negatives' => 'nullable|string',
            
            'motivation' => 'required|integer|min:0|max:4',
            'motivation_positives' => 'nullable|string',
            'motivation_negatives' => 'nullable|string',
            
            'internal_remarks' => 'nullable|string',
            'final_comment' => 'nullable|string',
            'result' => 'required|boolean',
            'next_step' => 'nullable|string',
        ]);

        try {
            $log->update($validated);

            ActivityLogger::log(
                'traininglog.updated',
                $log,
                "{$user->name} updated training log for {$log->trainee->name}",
                [
                    'log_id' => $log->id,
                    'trainee_id' => $log->trainee_id,
                    'trainee_name' => $log->trainee->name,
                    'session_date' => $validated['session_date'],
                    'result' => $validated['result'] ? 'Pass' : 'Fail',
                ],
                $user->id
            );

            Log::info('Training log updated', [
                'log_id' => $log->id,
                'mentor_id' => $user->id,
            ]);

            return redirect()
                ->route('training-logs.show', $log->id)
                ->with('success', 'Training log updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating training log', [
                'log_id' => $log->id,
                'mentor_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the training log.']);
        }
    }

    /**
     * Remove the specified training log from storage
     */
    public function destroy(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $log = TrainingLog::findOrFail($id);

        if ($user->id !== $log->mentor_id && !$user->is_superuser) {
            return back()->withErrors(['error' => 'You do not have permission to delete this log.']);
        }

        try {
            $traineeId = $log->trainee_id;
            $traineeName = $log->trainee->name;

            ActivityLogger::log(
                'traininglog.removed',
                null,
                "{$user->name} deleted training log for {$traineeName}",
                [
                    'log_id' => $id,
                    'trainee_id' => $traineeId,
                    'trainee_name' => $traineeName,
                    'session_date' => $log->session_date->format('Y-m-d'),
                ],
                $user->id
            );

            $log->delete();

            Log::info('Training log deleted', [
                'log_id' => $id,
                'mentor_id' => $user->id,
                'trainee_id' => $traineeId,
            ]);

            return redirect()
                ->route('training-logs.index')
                ->with('success', 'Training log deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Error deleting training log', [
                'log_id' => $id,
                'mentor_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while deleting the training log.']);
        }
    }

    /**
     * Get logs for a specific trainee
     */
    public function getTraineeLogs(Request $request, int $traineeId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $trainee = User::findOrFail($traineeId);

        $canView = $user->id === $traineeId 
            || $user->is_superuser 
            || $user->is_admin
            || $user->isMentor();

        if (!$canView) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $logs = TrainingLog::with(['mentor', 'course'])
            ->forTrainee($traineeId)
            ->recent()
            ->get()
            ->map(function ($log) use ($user) {
                $canViewInternal = $user->id === $log->mentor_id 
                    || $user->is_superuser 
                    || $user->is_admin
                    || ($log->course && $user->mentorCourses()->where('courses.id', $log->course_id)->exists());

                return $this->formatLogForFrontend($log, $canViewInternal);
            });

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    /**
     * Get logs for a specific course
     */
    public function getCourseLogs(Request $request, int $courseId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $course = Course::findOrFail($courseId);

        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $logs = TrainingLog::with(['trainee', 'mentor'])
            ->forCourse($courseId)
            ->recent()
            ->get()
            ->map(function ($log) {
                return $this->formatLogForFrontend($log, true);
            });

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    /**
     * Get statistics for a trainee
     */
    public function getTraineeStatistics(Request $request, int $traineeId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $canView = $user->id === $traineeId 
            || $user->is_superuser 
            || $user->is_admin
            || $user->isMentor();

        if (!$canView) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $logs = TrainingLog::forTrainee($traineeId)->get();

        $statistics = [
            'total_sessions' => $logs->count(),
            'passed_sessions' => $logs->where('result', true)->count(),
            'failed_sessions' => $logs->where('result', false)->count(),
            'average_rating' => $logs->avg(function ($log) {
                return $log->average_rating;
            }),
            'total_duration' => $logs->sum('session_duration'),
            'sessions_by_type' => [
                'online' => $logs->where('type', 'O')->count(),
                'sim' => $logs->where('type', 'S')->count(),
                'lesson' => $logs->where('type', 'L')->count(),
                'custom' => $logs->where('type', 'C')->count(),
            ],
            'recent_sessions' => $logs->sortByDesc('session_date')->take(5)->map(function ($log) {
                return [
                    'id' => $log->id,
                    'session_date' => $log->session_date->format('Y-m-d'),
                    'position' => $log->position,
                    'type' => $log->type_display,
                    'result' => $log->result,
                ];
            })->values(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Format log for frontend display
     */
    protected function formatLogForFrontend(TrainingLog $log, bool $includeInternal = false): array
    {
        $formatted = [
            'id' => $log->id,
            'session_date' => $log->session_date->format('Y-m-d'),
            'position' => $log->position,
            'type' => $log->type,
            'type_display' => $log->type_display,
            
            'traffic_level' => $log->traffic_level,
            'traffic_level_display' => $log->traffic_level_display,
            'traffic_complexity' => $log->traffic_complexity,
            'traffic_complexity_display' => $log->traffic_complexity_display,
            'runway_configuration' => $log->runway_configuration,
            'surrounding_stations' => $log->surrounding_stations,
            'session_duration' => $log->session_duration,
            'special_procedures' => $log->special_procedures,
            'airspace_restrictions' => $log->airspace_restrictions,
            
            'trainee' => [
                'id' => $log->trainee->id,
                'name' => $log->trainee->name,
                'vatsim_id' => $log->trainee->vatsim_id,
            ],
            'mentor' => $log->mentor ? [
                'id' => $log->mentor->id,
                'name' => $log->mentor->name,
                'vatsim_id' => $log->mentor->vatsim_id,
            ] : null,
            
            'course' => $log->course ? [
                'id' => $log->course->id,
                'name' => $log->course->name,
                'position' => $log->course->position,
                'type' => $log->course->type,
            ] : null,
            
            'evaluations' => $log->getEvaluationCategories(),
            
            'final_comment' => $log->final_comment,
            'result' => $log->result,
            'next_step' => $log->next_step,
            
            'average_rating' => $log->average_rating,
            'has_ratings' => $log->hasRatings(),
            'created_at' => $log->created_at->toIso8601String(),
            'updated_at' => $log->updated_at->toIso8601String(),
        ];

        if ($includeInternal) {
            $formatted['internal_remarks'] = $log->internal_remarks;
        }

        return $formatted;
    }

    /**
     * Get evaluation categories with descriptions
     */
    protected function getEvaluationCategories(): array
    {
        return [
            [
                'name' => 'theory',
                'label' => 'Theory',
                'description' => 'Applies required knowledge including airspace structure, SOPs, LoAs.',
            ],
            [
                'name' => 'phraseology',
                'label' => 'Phraseology/Radiotelephony',
                'description' => 'Applies correct phraseology in English and German.',
            ],
            [
                'name' => 'coordination',
                'label' => 'Coordination',
                'description' => 'Performs the required coordination with neighboring stations clearly and effectively. Hands/takes over station correctly.',
            ],
            [
                'name' => 'tag_management',
                'label' => 'Tag Management/FPL Handling',
                'description' => 'Keeps flight plan and tag up to date at all times.',
            ],
            [
                'name' => 'situational_awareness',
                'label' => 'Situational Awareness',
                'description' => 'Aware of the current and future traffic situation. Takes new information into account.',
            ],
            [
                'name' => 'problem_recognition',
                'label' => 'Problem Recognition',
                'description' => 'Recognizes problems early and reacts accordingly.',
            ],
            [
                'name' => 'traffic_planning',
                'label' => 'Traffic Planning',
                'description' => 'Looks ahead and plans a secure and efficient traffic flow.',
            ],
            [
                'name' => 'reaction',
                'label' => 'Reaction',
                'description' => 'Reacts in a timely manner, flexible and appropriate to changes in the current traffic situation.',
            ],
            [
                'name' => 'separation',
                'label' => 'Separation',
                'description' => 'Applies prescribed separation minima at all times (i.e. runway, radar, wake turbulence, separation etc.).',
            ],
            [
                'name' => 'efficiency',
                'label' => 'Efficiency',
                'description' => 'Takes pilot\'s requests into account, handles traffic in an efficient way for himself, the downstream sector and the pilot.',
            ],
            [
                'name' => 'ability_to_work_under_pressure',
                'label' => 'Ability to Work Under Pressure',
                'description' => 'Shows consistent performance regardless of traffic volume. Recovery from mistakes.',
            ],
            [
                'name' => 'motivation',
                'label' => 'Manner and Motivation',
                'description' => 'Is open to feedback and makes a realistic assessment of own performance. Deals respectfully with others and is well prepared for the session.',
            ],
        ];
    }

    /**
     * Get session types
     */
    protected function getSessionTypes(): array
    {
        return [
            ['value' => 'O', 'label' => 'Online'],
            ['value' => 'S', 'label' => 'Sim'],
            ['value' => 'L', 'label' => 'Lesson'],
        ];
    }

    /**
     * Get rating options
     */
    protected function getRatingOptions(): array
    {
        return [
            ['value' => 0, 'label' => 'Not Rated'],
            ['value' => 1, 'label' => 'Requirements Not Met'],
            ['value' => 2, 'label' => 'Requirements Partially Met'],
            ['value' => 3, 'label' => 'Requirements Met'],
            ['value' => 4, 'label' => 'Requirements Exceeded'],
        ];
    }

    /**
     * Get traffic levels
     */
    protected function getTrafficLevels(): array
    {
        return [
            ['value' => 'L', 'label' => 'Low'],
            ['value' => 'M', 'label' => 'Medium'],
            ['value' => 'H', 'label' => 'High'],
        ];
    }
}