<?php

namespace App\Filament\Widgets;

use App\Models\WaitingListEntry;
use Filament\Widgets\ChartWidget;

class WaitingListEntryOverviewChart extends ChartWidget
{
    protected ?string $heading = 'Waiting List Entries by Course Type';

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Count waiting list entries grouped by the course type
        $entriesByType = WaitingListEntry::query()
            ->join('courses', 'waiting_list_entries.course_id', '=', 'courses.id')
            ->select('courses.type')
            ->selectRaw('COUNT(waiting_list_entries.id) as count')
            ->groupBy('courses.type')
            ->pluck('count', 'courses.type')
            ->toArray();

        $labels = [];
        $data = [];

        foreach ($entriesByType as $type => $count) {
            $labels[] = match($type) {
                'RTG' => 'Rating',
                'EDMT' => 'Endorsement',
                'GST' => 'Visitor',
                'FAM' => 'Familiarisation',
                'RST' => 'Roster Reentry',
                default => $type ?? 'Unknown',
            };
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Waiting List Entries',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(251, 191, 36)',
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                        'rgb(107, 114, 128)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
