<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TraineeDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Trainee Distribution by Position';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $distribution = DB::table('course_trainees')
            ->join('courses', 'course_trainees.course_id', '=', 'courses.id')
            ->select('courses.position')
            ->selectRaw('count(course_trainees.user_id) as count')
            ->groupBy('courses.position')
            ->get();

        $labels = [];
        $data = [];

        foreach ($distribution as $item) {
            $labels[] = match($item->position) {
                'GND' => 'Ground',
                'TWR' => 'Tower',
                'APP' => 'Approach',
                'CTR' => 'Centre',
                default => $item->position,
            };
            $data[] = $item->count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Active Trainees',
                    'data' => $data,
                    'backgroundColor' => 'rgb(59, 130, 246)',
                    'borderColor' => 'rgb(29, 78, 216)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}