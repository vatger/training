<?php

namespace App\Filament\Resources\EndorsementActivities\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;

class EndorsementActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $minActivityMinutes = config('services.vateud.min_activity_minutes', 180);
        $minActivityHours = $minActivityMinutes / 60;

        return $schema
            ->components([
                Section::make('Endorsement Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('vatsim_id')
                                    ->label('VATSIM ID')
                                    ->content(fn($record) => $record->vatsim_id),

                                Placeholder::make('position')
                                    ->label('Position')
                                    ->content(fn($record) => new \Illuminate\Support\HtmlString(
                                        '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-blue-50 text-blue-700 ring-blue-600/20">'
                                        . e($record->position)
                                        . '</span>'
                                    )),

                                Placeholder::make('endorsement_id')
                                    ->label('Endorsement ID')
                                    ->content(fn($record) => $record->endorsement_id ?? '-'),
                            ]),
                    ])->columns(1),

                Section::make('Activity Status')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('activity_hours')
                                    ->label('Activity Hours')
                                    ->content(fn($record) => new \Illuminate\Support\HtmlString(
                                        '<span class="text-lg font-bold text-' 
                                        . ($record->status === 'active' ? 'success' : ($record->status === 'warning' ? 'warning' : 'danger'))
                                        . '-600">'
                                        . number_format($record->activity_hours, 1) . 'h'
                                        . '</span>'
                                    )),

                                Placeholder::make('required_hours')
                                    ->label('Required Hours')
                                    ->content(fn($record) => $minActivityHours . 'h'),

                                Placeholder::make('progress')
                                    ->label('Progress')
                                    ->content(fn($record) => new \Illuminate\Support\HtmlString(
                                        '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-'
                                        . ($record->progress >= 100 ? 'success' : ($record->progress >= 50 ? 'warning' : 'danger'))
                                        . '-50 text-'
                                        . ($record->progress >= 100 ? 'success' : ($record->progress >= 50 ? 'warning' : 'danger'))
                                        . '-700 ring-'
                                        . ($record->progress >= 100 ? 'success' : ($record->progress >= 50 ? 'warning' : 'danger'))
                                        . '-600/20">'
                                        . number_format($record->progress, 0) . '%'
                                        . '</span>'
                                    )),

                                Placeholder::make('status')
                                    ->label('Status')
                                    ->content(fn($record) => new \Illuminate\Support\HtmlString(
                                        '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-'
                                        . ($record->status === 'active' ? 'success' : ($record->status === 'warning' ? 'warning' : 'danger'))
                                        . '-50 text-'
                                        . ($record->status === 'active' ? 'success' : ($record->status === 'warning' ? 'warning' : 'danger'))
                                        . '-700 ring-'
                                        . ($record->status === 'active' ? 'success' : ($record->status === 'warning' ? 'warning' : 'danger'))
                                        . '-600/20">'
                                        . match($record->status) {
                                            'active' => 'Active',
                                            'warning' => 'Low Activity',
                                            'removal' => 'In Removal',
                                            default => ucfirst($record->status)
                                        }
                                        . '</span>'
                                    )),
                            ]),
                    ])->columns(1),

                Section::make('Activity Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('last_activity_date')
                                    ->label('Last Activity')
                                    ->content(fn($record) => $record->last_activity_date 
                                        ? $record->last_activity_date->format('Y-m-d') . ' (' . $record->last_activity_date->diffForHumans() . ')'
                                        : '-'),

                                Placeholder::make('last_updated')
                                    ->label('Last Updated')
                                    ->content(fn($record) => $record->last_updated 
                                        ? $record->last_updated->format('Y-m-d H:i:s') . ' (' . $record->last_updated->diffForHumans() . ')'
                                        : '-'),

                                Placeholder::make('created_at_vateud')
                                    ->label('Added to VatEUD')
                                    ->content(fn($record) => $record->created_at_vateud 
                                        ? $record->created_at_vateud->format('Y-m-d')
                                        : '-'),
                            ]),
                    ])->columns(1),

                Section::make('Removal Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('removal_date')
                                    ->label('Removal Date')
                                    ->content(fn($record) => $record->removal_date 
                                        ? new \Illuminate\Support\HtmlString(
                                            '<span class="text-danger-600 font-medium">'
                                            . $record->removal_date->format('Y-m-d')
                                            . ' (' . $record->removal_date->diffForHumans() . ')'
                                            . '</span>'
                                        )
                                        : '-'),

                                Placeholder::make('removal_notified')
                                    ->label('Notification Sent')
                                    ->content(fn($record) => $record->removal_notified 
                                        ? '✓ Yes' 
                                        : '✗ No'),
                            ]),
                    ])
                    ->visible(fn($record) => $record->removal_date !== null)
                    ->columns(1),

                Section::make('System Information')
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