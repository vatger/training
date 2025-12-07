<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\WaitingLists\WaitingListResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\View as ViewComponent;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        $record = $this->record;

        return $schema
            ->components([
                Section::make('Activity Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('id')
                                    ->label('Log ID')
                                    ->content(fn($record) => $record->id),

                                Placeholder::make('action')
                                    ->label('Action')
                                    ->content(fn($record) => new \Illuminate\Support\HtmlString(
                                        '<span class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-'
                                        . $record->getActionColor() . '-50 text-' . $record->getActionColor()
                                        . '-700 ring-' . $record->getActionColor() . '-600/20">'
                                        . e($record->getActionLabel())
                                        . '</span>'
                                    )),

                                Placeholder::make('created_at')
                                    ->label('Timestamp')
                                    ->content(fn($record) => $record->created_at->format('Y-m-d H:i:s')),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Placeholder::make('user')
                                    ->label('Performed By')
                                    ->content(
                                        fn($record) => $record->user
                                        ? new \Illuminate\Support\HtmlString(
                                            '<a href="' . UserResource::getUrl('edit', ['record' => $record->user]) . '" class="text-primary-600 hover:underline font-medium">'
                                            . e($record->user->name)
                                            . ' (' . e($record->user->vatsim_id) . ')'
                                            . '</a>'
                                        )
                                        : 'System'
                                    ),
                            ]),
                    ])->columns(1),

                Section::make('Description')
                    ->schema([
                        Placeholder::make('description')
                            ->hiddenLabel()
                            ->content(fn ($record) => $record->description ?? 'No description provided')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Subject Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('model_type')
                                    ->label('Subject Type')
                                    ->content(fn($record) => $record->model_type ? class_basename($record->model_type) : '-'),

                                Placeholder::make('model_id')
                                    ->label('Subject ID')
                                    ->content(
                                        fn($record) => $record->model_id
                                        ? self::getModelLink($record->model_type, $record->model_id)
                                        : '-'
                                    ),

                                Placeholder::make('model_exists')
                                    ->label('Status')
                                    ->content(
                                        fn($record) => $record->model_id && $record->model_type
                                        ? (self::modelExists($record->model_type, $record->model_id)
                                            ? new \Illuminate\Support\HtmlString('<span class="text-success-600 font-medium">✓ Exists</span>')
                                            : new \Illuminate\Support\HtmlString('<span class="text-danger-600 font-medium">✗ Deleted</span>')
                                        )
                                        : '-'
                                    ),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->model_type !== null)
                    ->collapsible()
                    ->columns(1),

                // Enhanced Changes Section
                Section::make('What Changed')
                    ->schema([
                        ViewComponent::make('filament.components.activity-log-changes')
                            ->viewData([
                                'changes' => $record->properties['changes'] ?? [],
                                'old' => $record->properties['old'] ?? [],
                                'new' => $record->properties['new'] ?? [],
                            ])
                    ])
                    ->visible(fn($record) => !empty($record->properties['changes']) || !empty($record->properties['old']))
                    ->collapsible(),

                // Related Records Section
                Section::make('Related Records')
                    ->schema([
                        Grid::make(2)
                            ->schema(self::getRelatedRecordsFields($record))
                    ])
                    ->visible(fn($record) => self::hasRelatedRecords($record))
                    ->collapsible(),

                Section::make('Additional Context')
                    ->schema([
                        KeyValue::make('properties')
                            ->label('All Properties')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->properties))
                    ->collapsible()
                    ->collapsed(),

                Section::make('Request Information')
                    ->schema([
                        Placeholder::make('user_agent')
                            ->label('User Agent')
                            ->content(fn ($record) => $record->user_agent ?? '-')
                            ->columnSpanFull(),
                        Placeholder::make('ip_address')
                            ->label('IP Address')
                            ->content(fn($record) => $record->ip_address ?: '-'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    protected static function getRelatedRecordsFields($record): array
    {
        $fields = [];
        $properties = $record->properties ?? [];

        // Course
        if (!empty($properties['course_id'])) {
            $fields[] = Placeholder::make('course_link')
                ->label('Course')
                ->content(fn() => self::getModelLink('App\Models\Course', $properties['course_id'], $properties['course_name'] ?? null));
        }

        // Trainee
        if (!empty($properties['trainee_id'])) {
            $fields[] = Placeholder::make('trainee_link')
                ->label('Trainee')
                ->content(fn() => self::getModelLink('App\Models\User', $properties['trainee_id'], $properties['trainee_name'] ?? null));
        }

        // Mentor
        if (!empty($properties['mentor_id'])) {
            $fields[] = Placeholder::make('mentor_link')
                ->label('Mentor')
                ->content(fn() => self::getModelLink('App\Models\User', $properties['mentor_id'], $properties['mentor_name'] ?? null));
        }

        // New Mentor (for assignments)
        if (!empty($properties['new_mentor_id'])) {
            $fields[] = Placeholder::make('new_mentor_link')
                ->label('Assigned To')
                ->content(fn() => self::getModelLink('App\Models\User', $properties['new_mentor_id'], $properties['new_mentor_name'] ?? null));
        }

        // Causer (if different from user)
        if (!empty($properties['causer_id']) && $properties['causer_id'] != $record->user_id) {
            $fields[] = Placeholder::make('causer_link')
                ->label('Causer')
                ->content(fn() => self::getModelLink('App\Models\User', $properties['causer_id'], $properties['causer_name'] ?? null));
        }

        return $fields;
    }

    protected static function hasRelatedRecords($record): bool
    {
        $properties = $record->properties ?? [];
        return !empty($properties['course_id'])
            || !empty($properties['trainee_id'])
            || !empty($properties['mentor_id'])
            || !empty($properties['new_mentor_id'])
            || (!empty($properties['causer_id']) && $properties['causer_id'] != $record->user_id);
    }

    protected static function getModelLink(?string $modelType, ?int $modelId, ?string $label = null): \Illuminate\Support\HtmlString|string
    {
        if (!$modelType || !$modelId) {
            return '-';
        }
    
        $resourceMap = [
            'App\Models\User' => UserResource::class,
            'App\Models\Course' => CourseResource::class,
            'App\Models\WaitingListEntry' => WaitingListResource::class,
        ];

        if (!isset($resourceMap[$modelType])) {
            return $label ?? (string) $modelId;
        }

        $resourceClass = $resourceMap[$modelType];

        try {
            $model = $modelType::find($modelId);
            
            if (!$model) {
                return ($label ?? $modelId) . ' (deleted)';
            }

            $url = $resourceClass::getUrl('edit', ['record' => $model]);
            $displayText = $label ?? $modelId;
            
            return new \Illuminate\Support\HtmlString(
                '<a href="' . e($url) . '" class="text-primary-600 hover:underline font-medium">'
                . e($displayText)
                . '</a>'
            );
        } catch (\Exception $e) {
            return $label ?? (string) $modelId;
        }
    }

    protected static function modelExists(?string $modelType, ?int $modelId): bool
    {
        if (!$modelType || !$modelId) {
            return false;
        }

        try {
            return $modelType::where('id', $modelId)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }
}