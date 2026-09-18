<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\UserStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        $user = auth()->user();

        if (! $user || (! $user->is_superuser && ! $user->is_admin)) {
            return [
                UserStatsWidget::class,
            ];
        }

        return parent::getWidgets();
    }

    public function getHeading(): string|Htmlable
    {
        $user = auth()->user();

        if (! $user) {
            return 'Dashboard';
        }

        if (! $user->is_superuser && ! $user->is_admin) {
            $timeOfDay = $this->getGreeting();

            return "{$timeOfDay}, {$user->first_name}!";
        }

        return 'Dashboard';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $user = auth()->user();

        if (! $user || $user->is_superuser || $user->is_admin) {
            return null;
        }

        $roles = [];

        if ($user->isLeadingMentor()) {
            $firs = $user->getLeadingMentorFirs();
            $roles[] = 'Leading Mentor ('.implode(', ', $firs).')';
        }

        if (empty($roles)) {
            return 'Welcome to the admin panel';
        }

        return implode(' • ', $roles);
    }

    protected function getGreeting(): string
    {
        $hour = now()->hour;

        if ($hour < 12) {
            return 'Good morning';
        } elseif ($hour < 18) {
            return 'Good afternoon';
        } else {
            return 'Good evening';
        }
    }
}
