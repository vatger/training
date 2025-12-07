<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
  public function index(Request $request): Response
  {
    $user = $request->user();
    /* 
            if ($user->isMentor() || $user->is_superuser || $user->is_admin) {
                return $this->mentorDashboard($user);
            } */

    return $this->traineeDashboard($user);
  }

  protected function traineeDashboard($user)
  {
    $activeCourses = $user->activeCourses()
      ->with(['mentorGroup'])
      ->get()
      ->map(function ($course) use ($user) {
        $pivot = DB::table('course_trainees')
          ->where('course_id', $course->id)
          ->where('user_id', $user->id)
          ->first();

        $claimedBy = null;
        if ($pivot && $pivot->claimed_by_mentor_id) {
          $mentor = \App\Models\User::find($pivot->claimed_by_mentor_id);
          $claimedBy = $mentor ? $mentor->name : null;
        }

        $allLogs = \App\Models\TrainingLog::where('trainee_id', $user->id)
          ->where('course_id', $course->id)
          ->with(['mentor'])
          ->orderBy('session_date', 'desc')
          ->get()
          ->map(function ($log) {
            return [
              'id' => $log->id,
              'session_date' => $log->session_date->format('Y-m-d'),
              'position' => $log->position ?? 'N/A',
              'type' => $log->type ?? 'O',
              'type_display' => $log->type_display ?? 'Online',
              'result' => $log->result ?? false,
              'average_rating' => $log->average_rating ?? null,
              'mentor_name' => $log->mentor ? $log->mentor->name : 'Unknown',
            ];
          });

        return [
          'id' => $course->id,
          'name' => $course->name,
          'trainee_display_name' => $course->trainee_display_name ?? $course->name,
          'type' => $course->type,
          'type_display' => $course->type_display,
          'position' => $course->position,
          'position_display' => $course->position_display,
          'airport_icao' => $course->airport_icao,
          'claimed_by' => $claimedBy,
          'recent_logs' => $allLogs->take(3)->toArray(),
          'all_logs' => $allLogs->toArray(),
        ];
      });

    $completedData = DB::table('course_trainees')
      ->join('courses', 'course_trainees.course_id', '=', 'courses.id')
      ->where('course_trainees.user_id', $user->id)
      ->whereNotNull('course_trainees.completed_at')
      ->select(
        'courses.*',
        'course_trainees.completed_at'
      )
      ->orderBy('course_trainees.completed_at', 'desc')
      ->get();

    $completedCourses = collect();
    foreach ($completedData as $courseData) {
      $recentLogs = \App\Models\TrainingLog::where('trainee_id', $user->id)
        ->where('course_id', $courseData->id)
        ->with(['mentor'])
        ->orderBy('session_date', 'desc')
        ->take(3)
        ->get()
        ->map(function ($log) {
          return [
            'id' => $log->id,
            'session_date' => $log->session_date->format('Y-m-d'),
            'position' => $log->position ?? 'N/A',
            'type' => $log->type ?? 'O',
            'type_display' => $log->type_display ?? 'Online',
            'result' => $log->result ?? false,
            'average_rating' => $log->average_rating ?? null,
            'mentor_name' => $log->mentor ? $log->mentor->name : 'Unknown',
          ];
        });

      $completedCourses->push([
        'id' => $courseData->id,
        'name' => $courseData->name,
        'trainee_display_name' => $courseData->trainee_display_name ?? $courseData->name,
        'type' => $courseData->type,
        'position' => $courseData->position,
        'position_display' => $this->getPositionDisplay($courseData->position),
        'airport_icao' => $courseData->airport_icao,
        'completed_at' => \Carbon\Carbon::parse($courseData->completed_at)->format('Y-m-d'),
        'recent_logs' => $recentLogs->toArray(),
      ]);
    }

    $totalSessions = \App\Models\TrainingLog::where('trainee_id', $user->id)
      ->count();

    $moodleCourses = [];
    $moodleService = app(\App\Services\MoodleService::class);

    foreach ($activeCourses as $course) {
      $fullCourse = \App\Models\Course::find($course['id']);
      if ($fullCourse && $fullCourse->moodle_course_ids) {
        $moodleIds = is_array($fullCourse->moodle_course_ids)
          ? $fullCourse->moodle_course_ids
          : json_decode($fullCourse->moodle_course_ids, true);

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
    }

    $familiarisations = $user->familiarisations()
      ->with('sector')
      ->get()
      ->groupBy('sector.fir')
      ->map(function ($fams) {
        return $fams->map(function ($fam) {
          return [
            'id' => $fam->id,
            'sector_name' => $fam->sector->name,
            'fir' => $fam->sector->fir,
          ];
        })->values();
      });

    return Inertia::render('trainee-dashboard', [
      'statistics' => [
        'active_courses' => $activeCourses->count(),
        'total_sessions' => $totalSessions,
        'completed_courses' => $completedCourses->count(),
      ],
      'activeCourses' => $activeCourses->values()->toArray(),
      'completedCourses' => $completedCourses->toArray(),
      'moodleCourses' => $moodleCourses,
      'familiarisations' => $familiarisations->toArray(),
    ]);
  }


  protected function getPositionDisplay(string $position): string
  {
    return match ($position) {
      'GND' => 'Ground',
      'TWR' => 'Tower',
      'APP' => 'Approach',
      'CTR' => 'Centre',
      default => $position,
    };
  }
}