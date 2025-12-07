<?php

namespace App\Filament\Resources\Cpts\Pages;

use App\Filament\Resources\Cpts\CptResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Placeholder;

class ViewCpt extends ViewRecord
{
    protected static string $resource = CptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->record;

        return $schema
            ->components([
                Section::make('CPT Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('id')
                                    ->label('CPT ID')
                                    ->content(fn($record) => $record->id),

                                Placeholder::make('date')
                                    ->label('CPT Date & Time')
                                    ->content(fn($record) => $record->date->format('Y-m-d H:i')),

                                Placeholder::make('confirmed')
                                    ->label('Confirmed')
                                    ->content(fn($record) => $record->confirmed ? '✓ Yes' : '✗ No'),
                            ]),
                    ])->columns(1),

                Section::make('Participants')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('trainee')
                                    ->label('Trainee')
                                    ->content(fn($record) => $record->trainee 
                                        ? new \Illuminate\Support\HtmlString(
                                            '<a href="' . UserResource::getUrl('edit', ['record' => $record->trainee]) . '" class="text-primary-600 hover:underline font-medium">'
                                            . e($record->trainee->name) . ' (' . e($record->trainee->vatsim_id) . ')'
                                            . '</a>'
                                        )
                                        : '-'
                                    ),

                                Placeholder::make('examiner')
                                    ->label('Examiner')
                                    ->content(fn($record) => $record->examiner 
                                        ? new \Illuminate\Support\HtmlString(
                                            '<a href="' . UserResource::getUrl('edit', ['record' => $record->examiner]) . '" class="text-primary-600 hover:underline font-medium">'
                                            . e($record->examiner->name) . ' (' . e($record->examiner->vatsim_id) . ')'
                                            . '</a>'
                                        )
                                        : 'Not assigned'
                                    ),

                                Placeholder::make('local')
                                    ->label('Local Contact')
                                    ->content(fn($record) => $record->local 
                                        ? new \Illuminate\Support\HtmlString(
                                            '<a href="' . UserResource::getUrl('edit', ['record' => $record->local]) . '" class="text-primary-600 hover:underline font-medium">'
                                            . e($record->local->name) . ' (' . e($record->local->vatsim_id) . ')'
                                            . '</a>'
                                        )
                                        : 'Not assigned'
                                    ),
                            ]),
                    ])->columns(1),

                Section::make('Course Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('course')
                                    ->label('Course')
                                    ->content(fn($record) => $record->course 
                                        ? new \Illuminate\Support\HtmlString(
                                            '<a href="' . CourseResource::getUrl('edit', ['record' => $record->course]) . '" class="text-primary-600 hover:underline font-medium">'
                                            . e($record->course->name)
                                            . '</a>'
                                        )
                                        : '-'
                                    ),

                                Placeholder::make('solo_station')
                                    ->label('Solo Station')
                                    ->content(fn($record) => $record->course?->solo_station ?? '-'),

                                Placeholder::make('position')
                                    ->label('Position')
                                    ->content(fn($record) => $record->course?->position ?? '-'),
                            ]),
                    ])->columns(1),

                Section::make('Result')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('log_uploaded')
                                    ->label('Log Uploaded')
                                    ->content(fn($record) => $record->log_uploaded 
                                        ? new \Illuminate\Support\HtmlString('<span class="text-success-600 font-medium">✓ Yes</span>')
                                        : new \Illuminate\Support\HtmlString('<span class="text-warning-600 font-medium">✗ No</span>')
                                    ),

                                Placeholder::make('passed')
                                    ->label('Result')
                                    ->content(fn($record) => match($record->passed) {
                                        true => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-700 ring-success-600/20">Passed</span>'),
                                        false => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-danger-50 text-danger-700 ring-danger-600/20">Failed</span>'),
                                        null => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-warning-50 text-warning-700 ring-warning-600/20">Pending</span>'),
                                    }),
                            ]),
                    ])->columns(1),

                Section::make('Logs')
                    ->schema([
                        Placeholder::make('logs_count')
                            ->label('Number of Logs')
                            ->content(fn($record) => $record->logs()->count()),
                    ])
                    ->collapsible(),

                Section::make('Timestamps')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('created_at')
                                    ->label('Created')
                                    ->content(fn($record) => $record->created_at->format('Y-m-d H:i:s')),

                                Placeholder::make('updated_at')
                                    ->label('Updated')
                                    ->content(fn($record) => $record->updated_at->format('Y-m-d H:i:s')),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}