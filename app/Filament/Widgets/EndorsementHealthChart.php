<?php

namespace App\Filament\Widgets;

use App\Models\EndorsementActivity;
use Filament\Widgets\ChartWidget;

class EndorsementHealthChart extends ChartWidget
{
    protected ?string $heading = 'Endorsement Activity Health';

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $minActivityMinutes = config('services.vateud.min_activity_minutes', 180);

        $active = EndorsementActivity::where('activity_minutes', '>=', $minActivityMinutes)
            ->whereNull('removal_date')
            ->count();

        $warning = EndorsementActivity::where('activity_minutes', '>=', $minActivityMinutes * 0.5)
            ->where('activity_minutes', '<', $minActivityMinutes)
            ->whereNull('removal_date')
            ->count();

        $critical = EndorsementActivity::where('activity_minutes', '<', $minActivityMinutes * 0.5)
            ->whereNull('removal_date')
            ->count();

        $inRemoval = EndorsementActivity::whereNotNull('removal_date')
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Endorsements',
                    'data' => [$active, $warning, $critical, $inRemoval],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',   // green - active
                        'rgb(251, 191, 36)',  // yellow - warning
                        'rgb(239, 68, 68)',   // red - critical
                        'rgb(107, 114, 128)', // gray - in removal
                    ],
                ],
            ],
            'labels' => [
                'Active (≥' . ($minActivityMinutes / 60) . 'h)',
                'Warning (50-100%)',
                'Critical (<50%)',
                'In Removal Process',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}