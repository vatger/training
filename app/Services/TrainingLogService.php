<?php

namespace App\Services;

use App\Models\TrainingLog;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainingLogService
{
    /**
     * Get comprehensive statistics for a trainee across all their logs
     */
    public function getTraineeProgressReport(int $traineeId): array
    {
        $logs = TrainingLog::with(['course', 'mentor'])
            ->forTrainee($traineeId)
            ->recent()
            ->get();

        if ($logs->isEmpty()) {
            return [
                'total_sessions' => 0,
                'total_hours' => 0,
                'average_rating' => 0,
                'pass_rate' => 0,
                'recent_trend' => 'no_data',
                'strengths' => [],
                'areas_for_improvement' => [],
            ];
        }

        return [
            'total_sessions' => $logs->count(),
            'total_hours' => round($logs->sum('session_duration') / 60, 1),
            'average_rating' => round($logs->avg(fn($log) => $log->average_rating), 2),
            'pass_rate' => round(($logs->where('result', true)->count() / $logs->count()) * 100, 1),
            'recent_trend' => $this->calculateRecentTrend($logs),
            'sessions_by_type' => $this->getSessionsByType($logs),
            'sessions_by_position' => $this->getSessionsByPosition($logs),
            'rating_breakdown' => $this->getRatingBreakdown($logs),
            'strengths' => $this->identifyStrengths($logs),
            'areas_for_improvement' => $this->identifyAreasForImprovement($logs),
            'recent_sessions' => $this->getRecentSessionsSummary($logs->take(5)),
        ];
    }

    /**
     * Calculate recent performance trend
     */
    protected function calculateRecentTrend(Collection $logs): string
    {
        if ($logs->count() < 3) {
            return 'insufficient_data';
        }

        $recentLogs = $logs->take(5);
        $olderLogs = $logs->skip(5)->take(5);

        if ($olderLogs->isEmpty()) {
            return 'new_trainee';
        }

        $recentAvg = $recentLogs->avg(fn($log) => $log->average_rating);
        $olderAvg = $olderLogs->avg(fn($log) => $log->average_rating);

        $difference = $recentAvg - $olderAvg;

        if ($difference >= 0.3) {
            return 'improving';
        } elseif ($difference <= -0.3) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    /**
     * Get sessions grouped by type
     */
    protected function getSessionsByType(Collection $logs): array
    {
        return [
            'online' => $logs->where('type', 'O')->count(),
            'sim' => $logs->where('type', 'S')->count(),
            'lesson' => $logs->where('type', 'L')->count(),
            'custom' => $logs->where('type', 'C')->count(),
        ];
    }

    /**
     * Get sessions grouped by position
     */
    protected function getSessionsByPosition(Collection $logs): array
    {
        return $logs->groupBy('position')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->take(10)
            ->toArray();
    }

    /**
     * Get detailed rating breakdown across all categories
     */
    protected function getRatingBreakdown(Collection $logs): array
    {
        $categories = [
            'theory', 'phraseology', 'coordination', 'tag_management',
            'situational_awareness', 'problem_recognition', 'traffic_planning',
            'reaction', 'separation', 'efficiency',
            'ability_to_work_under_pressure', 'motivation',
        ];

        $breakdown = [];

        foreach ($categories as $category) {
            $ratings = $logs->pluck($category)->filter(fn($r) => $r > 0);
            
            if ($ratings->isEmpty()) {
                continue;
            }

            $breakdown[$category] = [
                'average' => round($ratings->avg(), 2),
                'latest' => $logs->first()->$category ?? 0,
                'trend' => $this->calculateCategoryTrend($logs, $category),
            ];
        }

        return $breakdown;
    }

    /**
     * Calculate trend for a specific category
     */
    protected function calculateCategoryTrend(Collection $logs, string $category): string
    {
        if ($logs->count() < 3) {
            return 'insufficient_data';
        }

        $recent = $logs->take(3)->pluck($category)->filter(fn($r) => $r > 0);
        $older = $logs->skip(3)->take(3)->pluck($category)->filter(fn($r) => $r > 0);

        if ($recent->isEmpty() || $older->isEmpty()) {
            return 'insufficient_data';
        }

        $recentAvg = $recent->avg();
        $olderAvg = $older->avg();
        $difference = $recentAvg - $olderAvg;

        if ($difference >= 0.5) {
            return 'improving';
        } elseif ($difference <= -0.5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    /**
     * Identify trainee's strengths based on ratings
     */
    protected function identifyStrengths(Collection $logs): array
    {
        if ($logs->isEmpty()) {
            return [];
        }

        $categories = [
            'theory' => 'Theory',
            'phraseology' => 'Phraseology',
            'coordination' => 'Coordination',
            'tag_management' => 'Tag Management',
            'situational_awareness' => 'Situational Awareness',
            'problem_recognition' => 'Problem Recognition',
            'traffic_planning' => 'Traffic Planning',
            'reaction' => 'Reaction',
            'separation' => 'Separation',
            'efficiency' => 'Efficiency',
            'ability_to_work_under_pressure' => 'Ability to Work Under Pressure',
            'motivation' => 'Motivation',
        ];

        $strengths = [];

        foreach ($categories as $key => $label) {
            $ratings = $logs->pluck($key)->filter(fn($r) => $r > 0);
            
            if ($ratings->isEmpty()) {
                continue;
            }

            $average = $ratings->avg();
            
            // Consider it a strength if average >= 3.5 (between Met and Exceeded)
            if ($average >= 3.5) {
                $strengths[] = [
                    'category' => $key,
                    'label' => $label,
                    'average' => round($average, 2),
                ];
            }
        }

        // Sort by average rating descending
        usort($strengths, fn($a, $b) => $b['average'] <=> $a['average']);

        return array_slice($strengths, 0, 5); // Top 5 strengths
    }

    /**
     * Identify areas needing improvement based on ratings
     */
    protected function identifyAreasForImprovement(Collection $logs): array
    {
        if ($logs->isEmpty()) {
            return [];
        }

        $categories = [
            'theory' => 'Theory',
            'phraseology' => 'Phraseology',
            'coordination' => 'Coordination',
            'tag_management' => 'Tag Management',
            'situational_awareness' => 'Situational Awareness',
            'problem_recognition' => 'Problem Recognition',
            'traffic_planning' => 'Traffic Planning',
            'reaction' => 'Reaction',
            'separation' => 'Separation',
            'efficiency' => 'Efficiency',
            'ability_to_work_under_pressure' => 'Ability to Work Under Pressure',
            'motivation' => 'Motivation',
        ];

        $improvements = [];

        foreach ($categories as $key => $label) {
            $ratings = $logs->pluck($key)->filter(fn($r) => $r > 0);
            
            if ($ratings->isEmpty()) {
                continue;
            }

            $average = $ratings->avg();
            
            // Consider it an area for improvement if average < 3.0 (below Met)
            if ($average < 3.0) {
                $improvements[] = [
                    'category' => $key,
                    'label' => $label,
                    'average' => round($average, 2),
                    'recent_feedback' => $this->getRecentNegativeFeedback($logs, $key),
                ];
            }
        }

        // Sort by average rating ascending (worst first)
        usort($improvements, fn($a, $b) => $a['average'] <=> $b['average']);

        return array_slice($improvements, 0, 5); // Top 5 areas for improvement
    }

    /**
     * Get recent negative feedback for a category
     */
    protected function getRecentNegativeFeedback(Collection $logs, string $category): ?string
    {
        $negativesField = $category . '_negatives';
        
        $recentNegatives = $logs->take(3)
            ->pluck($negativesField)
            ->filter()
            ->first();

        return $recentNegatives;
    }

    /**
     * Get summary of recent sessions
     */
    protected function getRecentSessionsSummary(Collection $logs): array
    {
        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'session_date' => $log->session_date->format('Y-m-d'),
                'position' => $log->position,
                'type' => $log->type_display,
                'result' => $log->result,
                'average_rating' => $log->average_rating,
                'mentor_name' => $log->mentor->name,
            ];
        })->toArray();
    }

    /**
     * Get course-wide statistics
     */
    public function getCourseStatistics(int $courseId): array
    {
        $logs = TrainingLog::with(['trainee'])
            ->forCourse($courseId)
            ->get();

        if ($logs->isEmpty()) {
            return [
                'total_sessions' => 0,
                'unique_trainees' => 0,
                'average_rating' => 0,
                'pass_rate' => 0,
            ];
        }

        return [
            'total_sessions' => $logs->count(),
            'unique_trainees' => $logs->pluck('trainee_id')->unique()->count(),
            'total_hours' => round($logs->sum('session_duration') / 60, 1),
            'average_rating' => round($logs->avg(fn($log) => $log->average_rating), 2),
            'pass_rate' => round(($logs->where('result', true)->count() / $logs->count()) * 100, 1),
            'sessions_by_type' => $this->getSessionsByType($logs),
            'recent_activity' => $logs->sortByDesc('session_date')->take(10)->map(function ($log) {
                return [
                    'trainee_name' => $log->trainee->name,
                    'session_date' => $log->session_date->format('Y-m-d'),
                    'position' => $log->position,
                    'result' => $log->result,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Get mentor statistics
     */
    public function getMentorStatistics(int $mentorId): array
    {
        $logs = TrainingLog::with(['trainee', 'course'])
            ->byMentor($mentorId)
            ->get();

        if ($logs->isEmpty()) {
            return [
                'total_sessions' => 0,
                'unique_trainees' => 0,
                'total_hours' => 0,
                'average_rating_given' => 0,
            ];
        }

        return [
            'total_sessions' => $logs->count(),
            'unique_trainees' => $logs->pluck('trainee_id')->unique()->count(),
            'total_hours' => round($logs->sum('session_duration') / 60, 1),
            'average_rating_given' => round($logs->avg(fn($log) => $log->average_rating), 2),
            'pass_rate' => round(($logs->where('result', true)->count() / $logs->count()) * 100, 1),
            'sessions_by_course' => $logs->groupBy('course.name')
                ->map(fn($group) => $group->count())
                ->sortDesc()
                ->toArray(),
            'sessions_by_type' => $this->getSessionsByType($logs),
            'recent_sessions' => $logs->sortByDesc('session_date')->take(10)->map(function ($log) {
                return [
                    'trainee_name' => $log->trainee->name,
                    'course_name' => $log->course->name ?? 'N/A',
                    'session_date' => $log->session_date->format('Y-m-d'),
                    'position' => $log->position,
                    'result' => $log->result,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Compare trainee performance across multiple trainees
     */
    public function compareTrainees(array $traineeIds): array
    {
        $comparisons = [];

        foreach ($traineeIds as $traineeId) {
            $logs = TrainingLog::forTrainee($traineeId)->get();
            
            if ($logs->isEmpty()) {
                continue;
            }

            $trainee = User::find($traineeId);
            
            $comparisons[] = [
                'trainee_id' => $traineeId,
                'trainee_name' => $trainee->name,
                'total_sessions' => $logs->count(),
                'average_rating' => round($logs->avg(fn($log) => $log->average_rating), 2),
                'pass_rate' => round(($logs->where('result', true)->count() / $logs->count()) * 100, 1),
                'total_hours' => round($logs->sum('session_duration') / 60, 1),
            ];
        }

        return $comparisons;
    }

    /**
     * Generate a PDF report for a training log (placeholder for future implementation)
     */
    public function generatePdfReport(int $logId): string
    {
        // This would integrate with a PDF generation library
        // For now, return a placeholder path
        return "/storage/training-logs/{$logId}.pdf";
    }

    /**
     * Export training logs to CSV
     */
    public function exportToCsv(Collection $logs): string
    {
        $headers = [
            'Date', 'Trainee', 'Mentor', 'Course', 'Position', 'Type',
            'Duration (min)', 'Result', 'Average Rating',
            'Theory', 'Phraseology', 'Coordination', 'Tag Management',
            'Situational Awareness', 'Problem Recognition', 'Traffic Planning',
            'Reaction', 'Separation', 'Efficiency', 'Work Under Pressure', 'Motivation'
        ];

        $rows = $logs->map(function ($log) {
            return [
                $log->session_date->format('Y-m-d'),
                $log->trainee->name,
                $log->mentor->name,
                $log->course->name ?? 'N/A',
                $log->position,
                $log->type_display,
                $log->session_duration ?? 0,
                $log->result ? 'Pass' : 'Fail',
                $log->average_rating,
                $log->theory_display,
                $log->phraseology_display,
                $log->coordination_display,
                $log->tag_management_display,
                $log->situational_awareness_display,
                $log->problem_recognition_display,
                $log->traffic_planning_display,
                $log->reaction_display,
                $log->separation_display,
                $log->efficiency_display,
                $log->ability_to_work_under_pressure_display,
                $log->motivation_display,
            ];
        });

        // Generate CSV content
        $csv = [implode(',', $headers)];
        foreach ($rows as $row) {
            $csv[] = implode(',', array_map(function ($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row));
        }

        return implode("\n", $csv);
    }
}