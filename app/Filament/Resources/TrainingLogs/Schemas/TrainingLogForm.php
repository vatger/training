<?php

namespace App\Filament\Resources\TrainingLogs\Schemas;

use App\Models\TrainingLog;
use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class TrainingLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('trainee_id')
                            ->label('Trainee')
                            ->relationship('trainee', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->vatsim_id})")
                            ->searchable(['first_name', 'last_name', 'vatsim_id'])
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('mentor_id')
                            ->label('Mentor')
                            ->relationship('mentor', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->vatsim_id})")
                            ->searchable(['first_name', 'last_name', 'vatsim_id'])
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'name')
                            ->searchable()
                            ->required()
                            ->preload(),

                        Forms\Components\DatePicker::make('session_date')
                            ->label('Session Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('position')
                            ->label('Position')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('EDDF_TWR'),

                        Forms\Components\Select::make('type')
                            ->label('Session Type')
                            ->required()
                            ->options([
                                TrainingLog::TYPE_ONLINE => 'Online',
                                TrainingLog::TYPE_SIM => 'Sim',
                                TrainingLog::TYPE_LESSON => 'Lesson',
                                TrainingLog::TYPE_CUSTOM => 'Custom',
                            ]),
                    ])->columns(2),

                Section::make('Session Details')
                    ->schema([
                        Forms\Components\Select::make('traffic_level')
                            ->label('Traffic Level')
                            ->options([
                                TrainingLog::TRAFFIC_LOW => 'Low',
                                TrainingLog::TRAFFIC_MEDIUM => 'Medium',
                                TrainingLog::TRAFFIC_HIGH => 'High',
                            ]),

                        Forms\Components\Select::make('traffic_complexity')
                            ->label('Traffic Complexity')
                            ->options([
                                TrainingLog::TRAFFIC_LOW => 'Low',
                                TrainingLog::TRAFFIC_MEDIUM => 'Medium',
                                TrainingLog::TRAFFIC_HIGH => 'High',
                            ]),

                        Forms\Components\TextInput::make('session_duration')
                            ->label('Session Duration (minutes)')
                            ->numeric()
                            ->suffix('min'),

                        Forms\Components\TextInput::make('runway_configuration')
                            ->label('Runway Configuration')
                            ->maxLength(100),

                        Forms\Components\Textarea::make('surrounding_stations')
                            ->label('Surrounding Stations')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('special_procedures')
                            ->label('Special Procedures')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('airspace_restrictions')
                            ->label('Airspace Restrictions')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2)->collapsible(),

                Section::make('Evaluation')
                    ->schema([
                        self::getRatingField('theory', 'Theory'),
                        self::getRatingField('phraseology', 'Phraseology'),
                        self::getRatingField('coordination', 'Coordination'),
                        self::getRatingField('tag_management', 'Tag Management'),
                        self::getRatingField('situational_awareness', 'Situational Awareness'),
                        self::getRatingField('problem_recognition', 'Problem Recognition'),
                        self::getRatingField('traffic_planning', 'Traffic Planning'),
                        self::getRatingField('reaction', 'Reaction'),
                        self::getRatingField('separation', 'Separation'),
                        self::getRatingField('efficiency', 'Efficiency'),
                        self::getRatingField('ability_to_work_under_pressure', 'Ability to Work Under Pressure'),
                        self::getRatingField('motivation', 'Motivation'),
                    ])->collapsible(),

                Section::make('Final Assessment')
                    ->schema([
                        Forms\Components\Textarea::make('internal_remarks')
                            ->label('Internal Remarks')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('final_comment')
                            ->label('Final Comment')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('result')
                            ->label('Session Passed')
                            ->default(false),

                        Forms\Components\Textarea::make('next_step')
                            ->label('Next Step')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    protected static function getRatingField(string $field, string $label)
    {
        return Grid::make(1)
            ->schema([
                Forms\Components\Select::make($field)
                    ->label($label)
                    ->options([
                        TrainingLog::RATING_NOT_RATED => 'Not Rated',
                        TrainingLog::RATING_NOT_MET => 'Requirements Not Met',
                        TrainingLog::RATING_PARTIALLY_MET => 'Requirements Partially Met',
                        TrainingLog::RATING_MET => 'Requirements Met',
                        TrainingLog::RATING_EXCEEDED => 'Requirements Exceeded',
                    ])
                    ->default(TrainingLog::RATING_NOT_RATED),

                Forms\Components\Textarea::make("{$field}_positives")
                    ->label('Positives')
                    ->rows(2),

                Forms\Components\Textarea::make("{$field}_negatives")
                    ->label('Areas for Improvement')
                    ->rows(2),
            ]);
    }
}