<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserSearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $query = trim($request->input('query'));

        try {
            if (is_numeric($query)) {
                $users = User::where('vatsim_id', $query)
                    ->whereNotNull('vatsim_id')
                    ->limit(10)
                    ->get(['id', 'vatsim_id', 'first_name', 'last_name', 'email']);
            } else {
                $searchTerm = strtolower($query);

                $users = User::select(['id', 'vatsim_id', 'first_name', 'last_name', 'email'])
                    ->whereNotNull('vatsim_id')
                    ->where(function ($q) use ($searchTerm) {
                        $q->whereRaw('LOWER(first_name) LIKE ?', [$searchTerm . '%'])
                            ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchTerm . '%'])
                            ->orWhereRaw('LOWER(first_name || \' \' || last_name) LIKE ?', [$searchTerm . '%']);
                    })
                    ->orderByRaw("
                        CASE
                            WHEN LOWER(first_name) = ? THEN 1
                            WHEN LOWER(last_name) = ? THEN 2
                            WHEN LOWER(first_name) LIKE ? THEN 3
                            WHEN LOWER(last_name) LIKE ? THEN 4
                            ELSE 5
                        END
                    ", [$searchTerm, $searchTerm, $searchTerm . '%', $searchTerm . '%'])
                    ->limit(10)
                    ->get();

                if ($users->isEmpty() && strlen($searchTerm) > 2) {
                    $users = User::select(['id', 'vatsim_id', 'first_name', 'last_name', 'email'])
                        ->whereNotNull('vatsim_id')
                        ->where(function ($q) use ($searchTerm) {
                            $q->whereRaw('LOWER(first_name) LIKE ?', ['%' . $searchTerm . '%'])
                                ->orWhereRaw('LOWER(last_name) LIKE ?', ['%' . $searchTerm . '%'])
                                ->orWhereRaw('LOWER(first_name || \' \' || last_name) LIKE ?', ['%' . $searchTerm . '%']);
                        })
                        ->limit(10)
                        ->get();
                }
            }

            $results = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'vatsim_id' => $user->vatsim_id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            });

            return response()->json([
                'success' => true,
                'users' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('User search error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed'
            ], 500);
        }
    }

    public function show(int $vatsimId)
    {
        $user = User::where('vatsim_id', $vatsimId)
            ->whereNotNull('vatsim_id')
            ->firstOrFail();

        $currentUser = auth()->user();

        if (!$currentUser->isMentor() && !$currentUser->isSuperuser() && !$currentUser->is_admin) {
            abort(403, 'Only mentors can view user profiles.');
        }

        if ($currentUser->isSuperuser() || $currentUser->is_admin) {
            $mentorCourseIds = \App\Models\Course::pluck('id')->toArray();
        } else {
            $mentorCourseIds = $currentUser->mentorCourses()->pluck('courses.id')->toArray();
        }

        $activeCourses = $user->activeCourses()
            ->with(['mentorGroup'])
            ->whereIn('courses.id', $mentorCourseIds)
            ->get()
            ->map(function ($course) use ($mentorCourseIds, $user) {
                $isMentor = in_array($course->id, $mentorCourseIds);

                $courseData = [
                    'id' => $course->id,
                    'name' => $course->name,
                    'type' => $course->type,
                    'position' => $course->position,
                    'is_mentor' => $isMentor,
                    'logs' => [],
                ];

                if ($isMentor) {
                    try {
                        $logs = \App\Models\TrainingLog::where('course_id', $course->id)
                            ->where('trainee_id', $user->id)
                            ->with(['mentor:id,first_name,last_name'])
                            ->select([
                                'id',
                                'session_date',
                                'position',
                                'type',
                                'result',
                                'mentor_id',
                                'session_duration',
                                'next_step',
                            ])
                            ->orderBy('session_date', 'desc')
                            ->limit(5)
                            ->get()
                            ->map(function ($log) {
                                return [
                                    'id' => $log->id,
                                    'session_date' => $log->session_date->format('Y-m-d'),
                                    'position' => $log->position ?? 'N/A',
                                    'type' => $log->type ?? 'O',
                                    'type_display' => $log->type_display ?? 'Online',
                                    'result' => $log->result ?? false,
                                    'mentor_name' => $log->mentor ? "{$log->mentor->first_name} {$log->mentor->last_name}" : 'Unknown',
                                    'session_duration' => $log->session_duration ?? null,
                                    'next_step' => $log->next_step ?? null,
                                ];
                            });

                        $courseData['logs'] = $logs->toArray();
                    } catch (\Exception $e) {
                        \Log::error('Error fetching training logs', [
                            'course_id' => $course->id,
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                        $courseData['logs'] = [];
                    }
                }

                return $courseData;
            });

        $completedCourses = collect();

        try {
            $completedData = DB::table('course_trainees')
                ->join('courses', 'course_trainees.course_id', '=', 'courses.id')
                ->where('course_trainees.user_id', $user->id)
                ->whereNotNull('course_trainees.completed_at')
                ->whereIn('courses.id', $mentorCourseIds)
                ->select([
                    'courses.id',
                    'courses.name',
                    'courses.type',
                    'courses.position',
                    'course_trainees.completed_at'
                ])
                ->get();

            $courseIds = $completedData->pluck('id');

            $logsGrouped = \App\Models\TrainingLog::whereIn('course_id', $courseIds)
                ->where('trainee_id', $user->id)
                ->with(['mentor:id,first_name,last_name'])
                ->select([
                    'id',
                    'course_id',
                    'session_date',
                    'position',
                    'type',
                    'result',
                    'mentor_id',
                    'session_duration',
                    'next_step',
                ])
                ->orderBy('session_date', 'desc')
                ->get()
                ->groupBy('course_id');

            foreach ($completedData as $courseData) {
                $logs = $logsGrouped->get($courseData->id, collect())->take(10);

                $completedCourses->push([
                    'id' => $courseData->id,
                    'name' => $courseData->name,
                    'type' => $courseData->type,
                    'position' => $courseData->position,
                    'completed_at' => \Carbon\Carbon::parse($courseData->completed_at)->format('Y-m-d'),
                    'total_sessions' => $logsGrouped->get($courseData->id, collect())->count(),
                    'logs' => $logs->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'session_date' => $log->session_date->format('Y-m-d'),
                            'position' => $log->position ?? 'N/A',
                            'type' => $log->type ?? 'O',
                            'type_display' => $log->type_display ?? 'Online',
                            'result' => $log->result ?? false,
                            'mentor_name' => $log->mentor ? "{$log->mentor->first_name} {$log->mentor->last_name}" : 'Unknown',
                            'session_duration' => $log->session_duration ?? null,
                            'next_step' => $log->next_step ?? null,
                        ];
                    })->toArray(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching completed courses', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            $completedCourses = collect();
        }
        
        $endorsements = $user->endorsementActivities()
            ->select([
                'position',
                'activity_minutes',
                'last_updated',
                'last_activity_date',
                'removal_date',
                'removal_notified',
            ])
            ->get()
            ->map(function ($activity) {
                return [
                    'position' => $activity->position,
                    'activity_minutes' => $activity->activity_minutes,
                    'activity_hours' => $activity->activity_hours,
                    'status' => $activity->status,
                    'progress' => $activity->progress,
                    'last_updated' => $activity->last_updated?->format('Y-m-d'),
                    'last_activity_date' => $activity->last_activity_date?->format('Y-m-d'),
                    'removal_date' => $activity->removal_date?->format('Y-m-d'),
                    'removal_notified' => $activity->removal_notified,
                ];
            });


        $familiarisations = $user->familiarisations()
            ->with('sector:id,name,fir')
            ->get()
            ->groupBy('sector.fir')
            ->map(function($fams) {
                return $fams->map(function($fam) {
                    return [
                        'id' => $fam->id,
                        'sector_name' => $fam->sector->name,
                        'fir' => $fam->sector->fir,
                    ];
                })->values();
            });

        $moodleCourses = [];
        $moodleService = app(\App\Services\MoodleService::class);

        $courseIds = $activeCourses->pluck('id');
        $coursesWithMoodle = \App\Models\Course::whereIn('id', $courseIds)
            ->whereNotNull('moodle_course_ids')
            ->get();

        foreach ($coursesWithMoodle as $course) {
            $moodleIds = is_array($course->moodle_course_ids)
                ? $course->moodle_course_ids
                : json_decode($course->moodle_course_ids, true);

            if (is_array($moodleIds)) {
                foreach ($moodleIds as $moodleId) {
                    try {
                        $courseName = $moodleService->getCourseName($moodleId);
                        $isPassed = $moodleService->getCourseCompletion($user->vatsim_id, $moodleId);

                        $moodleCourses[] = [
                            'id' => $moodleId,
                            'name' => $courseName ?? "Moodle Course {$moodleId}",
                            'passed' => $isPassed,
                            'link' => "https://moodle.vatsim-germany.org/course/view.php?id={$moodleId}",
                        ];
                    } catch (\Exception $e) {
                        \Log::warning('Failed to fetch Moodle course info', [
                            'moodle_id' => $moodleId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        $userData = [
            'user' => [
                'vatsim_id' => $user->vatsim_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'rating' => $user->rating,
                'subdivision' => $user->subdivision,
                'last_rating_change' => $user->last_rating_change?->format('Y-m-d'),
                'is_mentor' => $user->isMentor(),
                'is_superuser' => $user->is_superuser,
                'is_admin' => $user->is_admin,
                'solo_days_used' => $user->solo_days_used,
            ],
            'active_courses' => $activeCourses->values()->toArray(),
            'completed_courses' => $completedCourses->toArray(),
            'endorsements' => $endorsements,
            'moodle_courses' => $moodleCourses,
            'familiarisations' => $familiarisations,
        ];

        return Inertia::render('users/profile', [
            'userData' => $userData,
        ]);
    }
}