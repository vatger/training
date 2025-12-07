<?php

namespace App\Filament\Resources\Cpts\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Schemas\Components\Section;

class CptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('CPT Information')
                    ->schema([
                        Forms\Components\DateTimePicker::make('date')
                            ->label('CPT Date & Time')
                            ->required()
                            ->seconds(false),

                        Forms\Components\Toggle::make('confirmed')
                            ->label('Confirmed')
                            ->disabled()
                            ->helperText('Automatically set when both examiner and local contact are assigned'),

                        Forms\Components\Toggle::make('log_uploaded')
                            ->label('Log Uploaded')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Participants')
                    ->schema([
                        Forms\Components\Select::make('trainee_id')
                            ->label('Trainee')
                            ->relationship('trainee', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->vatsim_id})")
                            ->searchable(['first_name', 'last_name', 'vatsim_id'])
                            ->required()
                            ->preload()
                            ->disabled(),

                        Forms\Components\Select::make('examiner_id')
                            ->label('Examiner')
                            ->relationship('examiner', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->vatsim_id})")
                            ->searchable(['first_name', 'last_name', 'vatsim_id'])
                            ->preload(),

                        Forms\Components\Select::make('local_id')
                            ->label('Local Contact')
                            ->relationship('local', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->vatsim_id})")
                            ->searchable(['first_name', 'last_name', 'vatsim_id'])
                            ->preload(),
                    ])->columns(3),

                Section::make('Course')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'name')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->disabled(),
                    ]),

                Section::make('Result')
                    ->schema([
                        Forms\Components\Select::make('passed')
                            ->label('Result')
                            ->options([
                                1 => 'Passed',
                                0 => 'Failed',
                            ])
                            ->placeholder('Pending')
                            ->helperText('Leave empty for pending result'),
                    ]),
            ]);
    }
}