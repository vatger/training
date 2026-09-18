<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class UserStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return ! $user->is_superuser && ! $user->is_admin;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $stats = [];

        if ($user->isLeadingMentor()) {
            $stats = array_merge($stats, $this->getLeadingMentorStats($user));
        }

        if (empty($stats)) {
            $stats = $this->getGenericStats($user);
        }

        return $stats;
    }

    protected function getLeadingMentorStats($user): array
    {
        $firs = $user->getLeadingMentorFirs();

        $courseCount = DB::table('courses')
            ->join('roles', 'courses.mentor_group_id', '=', 'roles.id')
            ->where(function ($q) use ($firs) {
                foreach ($firs as $fir) {
                    $q->orWhere('roles.name', 'LIKE', "%{$fir}%");
                }
            })
            ->count();

        $activeMentors = DB::table('users')
            ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where(function ($q) use ($firs) {
                foreach ($firs as $fir) {
                    $q->orWhere('roles.name', 'LIKE', "%{$fir} Mentor%");
                }
            })
            ->distinct()
            ->count('users.id');

        $activeStudents = DB::table('course_trainees')
            ->join('courses', 'course_trainees.course_id', '=', 'courses.id')
            ->join('roles', 'courses.mentor_group_id', '=', 'roles.id')
            ->whereNull('course_trainees.completed_at')
            ->where(function ($q) use ($firs) {
                foreach ($firs as $fir) {
                    $q->orWhere('roles.name', 'LIKE', "%{$fir}%");
                }
            })
            ->distinct()
            ->count('course_trainees.user_id');

        return [
            Stat::make('Your Courses', $courseCount)
                ->description('Courses under your supervision')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('success'),

            Stat::make('Active Mentors', $activeMentors)
                ->description('Mentors in your FIR(s)')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('Active Students', $activeStudents)
                ->description('Students currently in training')
                ->descriptionIcon('heroicon-o-users')
                ->color('warning'),
        ];
    }

    protected function getGenericStats($user): array
    {
        $activeCourses = $user->activeCourses()->count();

        $completedCourses = DB::table('course_trainees')
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->count();

        $lastLogin = $user->updated_at ? $user->updated_at->diffForHumans() : 'Never';

        return [
            Stat::make('Active Courses', $activeCourses)
                ->description('Courses you are currently enrolled in')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('warning'),

            Stat::make('Completed Courses', $completedCourses)
                ->description('Courses you have completed')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Account Status', 'Active')
                ->description("Last activity: {$lastLogin}")
                ->descriptionIcon('heroicon-o-user-circle')
                ->color('primary'),
        ];
    }
}
