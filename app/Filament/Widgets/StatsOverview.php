<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Models\EndorsementActivity;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Total users (VATSIM only)
        $totalUsers = User::whereNotNull('vatsim_id')->count();
        
        // Users registered in last 30 days
        $recentUsers = User::whereNotNull('vatsim_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Total active trainees across all courses
        $activeTrainees = DB::table('course_trainees')
            ->distinct('user_id')
            ->count('user_id');

        // Total waiting list entries
        $waitingListTotal = WaitingListEntry::count();

        // Active endorsements (with sufficient activity)
        $minActivityMinutes = config('services.vateud.min_activity_minutes', 180);
        $activeEndorsements = EndorsementActivity::where('activity_minutes', '>=', $minActivityMinutes)
            ->count();

        // Low activity endorsements (warning zone)
        $lowActivityEndorsements = EndorsementActivity::where('activity_minutes', '<', $minActivityMinutes)
            ->whereNull('removal_date')
            ->count();

        // Total courses
        $totalCourses = Course::count();

        // Courses by type
        $rtgCourses = Course::where('type', 'RTG')->count();

        return [
            Stat::make('Total Users', $totalUsers)
                ->description($recentUsers . ' new in last 30 days')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Active Trainees', $activeTrainees)
                ->description('Currently in training')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Waiting List', $waitingListTotal)
                ->description('Awaiting training')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Active Endorsements', $activeEndorsements)
                ->description($lowActivityEndorsements . ' with low activity')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($lowActivityEndorsements > 0 ? 'warning' : 'success'),

            Stat::make('Total Courses', $totalCourses)
                ->description($rtgCourses . ' rating courses')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('info'),

            Stat::make('Staff Members', User::where('is_staff', true)->count())
                ->description('Active mentors and leadership')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}