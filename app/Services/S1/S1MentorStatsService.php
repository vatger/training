<?php

namespace App\Services\S1;

use App\Models\S1\S1Session;
use App\Models\S1\S1SessionAttendance;
use App\Models\S1\S1ModuleCompletion;
use App\Models\User;
use Carbon\Carbon;

class S1MentorStatsService
{
    public function getMentorStats(User $mentor, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = S1Session::where('mentor_id', $mentor->id);

        if ($from) {
            $query->where('scheduled_at', '>=', $from);
        }

        if ($to) {
            $query->where('scheduled_at', '<=', $to);
        }

        $sessions = $query->with(['attendances', 'module'])->get();

        $stats = [
            'mentor_id' => $mentor->id,
            'mentor_name' => $mentor->name,
            'period' => [
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'sessions' => [
                'total' => $sessions->count(),
                'completed' => $sessions->where('attendance_completed', true)->count(),
                'upcoming' => $sessions->where('scheduled_at', '>', now())->count(),
                'by_module' => $this->getSessionsByModule($sessions),
            ],
            'trainees' => [
                'total_trained' => $this->getTotalTrainees($sessions),
                'passed' => $this->getPassedTrainees($sessions),
                'failed' => $this->getFailedTrainees($sessions),
                'attendance_rate' => $this->getAttendanceRate($sessions),
            ],
            'completions' => [
                'total' => $this->getCompletionCount($mentor, $from, $to),
                'by_module' => $this->getCompletionsByModule($mentor, $from, $to),
            ],
        ];

        return $stats;
    }

    public function getAllMentorsStats(?Carbon $from = null, ?Carbon $to = null): array
    {
        $mentorIds = S1Session::distinct('mentor_id')->pluck('mentor_id');
        $mentors = User::whereIn('id', $mentorIds)->get();

        return $mentors->map(function ($mentor) use ($from, $to) {
            return $this->getMentorStats($mentor, $from, $to);
        })->toArray();
    }

    protected function getSessionsByModule($sessions): array
    {
        return $sessions->groupBy('module_id')->map(function ($moduleSessions, $moduleId) {
            $module = $moduleSessions->first()->module;
            return [
                'module_id' => $moduleId,
                'module_name' => $module->name,
                'count' => $moduleSessions->count(),
            ];
        })->values()->toArray();
    }

    protected function getTotalTrainees($sessions): int
    {
        return S1SessionAttendance::whereIn('session_id', $sessions->pluck('id'))
            ->distinct('user_id')
            ->count();
    }

    protected function getPassedTrainees($sessions): int
    {
        return S1SessionAttendance::whereIn('session_id', $sessions->pluck('id'))
            ->where('status', 'passed')
            ->distinct('user_id')
            ->count();
    }

    protected function getFailedTrainees($sessions): int
    {
        return S1SessionAttendance::whereIn('session_id', $sessions->pluck('id'))
            ->where('status', 'failed')
            ->distinct('user_id')
            ->count();
    }

    protected function getAttendanceRate($sessions): float
    {
        $totalExpected = S1SessionAttendance::whereIn('session_id', $sessions->pluck('id'))
            ->whereIn('status', ['attended', 'absent', 'excused', 'passed', 'failed'])
            ->count();

        if ($totalExpected === 0) {
            return 0;
        }

        $attended = S1SessionAttendance::whereIn('session_id', $sessions->pluck('id'))
            ->whereIn('status', ['attended', 'passed'])
            ->count();

        return round(($attended / $totalExpected) * 100, 2);
    }

    protected function getCompletionCount(User $mentor, ?Carbon $from, ?Carbon $to): int
    {
        $query = S1ModuleCompletion::where('completed_by_mentor_id', $mentor->id);

        if ($from) {
            $query->where('completed_at', '>=', $from);
        }

        if ($to) {
            $query->where('completed_at', '<=', $to);
        }

        return $query->count();
    }

    protected function getCompletionsByModule(User $mentor, ?Carbon $from, ?Carbon $to): array
    {
        $query = S1ModuleCompletion::where('completed_by_mentor_id', $mentor->id)
            ->with('module');

        if ($from) {
            $query->where('completed_at', '>=', $from);
        }

        if ($to) {
            $query->where('completed_at', '<=', $to);
        }

        return $query->get()
            ->groupBy('module_id')
            ->map(function ($completions, $moduleId) {
                $module = $completions->first()->module;
                return [
                    'module_id' => $moduleId,
                    'module_name' => $module->name,
                    'count' => $completions->count(),
                ];
            })
            ->values()
            ->toArray();
    }
}