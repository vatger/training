<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use App\Models\Role;
use App\Models\FamiliarisationSector;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Course Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Full course name (e.g., "Frankfurt Tower S2")'),
                        
                        Forms\Components\TextInput::make('trainee_display_name')
                            ->label('Display Name for Trainees')
                            ->required()
                            ->maxLength(100)
                            ->helperText('How this course appears to trainees'),
                        
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Airport Details')
                    ->schema([
                        Forms\Components\TextInput::make('airport_name')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Full airport name (e.g., "Frankfurt")'),
                        
                        Forms\Components\TextInput::make('airport_icao')
                            ->label('Airport ICAO Code')
                            ->required()
                            ->maxLength(4)
                            ->placeholder('EDDF')
                            ->helperText('4-letter ICAO code'),
                        
                        Forms\Components\TextInput::make('solo_station')
                            ->label('Solo Station Callsign')
                            ->maxLength(15)
                            ->placeholder('EDDF_TWR')
                            ->helperText('Optional: Callsign for solo endorsements'),
                    ])->columns(2),

                Section::make('Course Settings')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Course Type')
                            ->required()
                            ->options([
                                'RTG' => 'Rating Course (RTG)',
                                'EDMT' => 'Endorsement Training (EDMT)',
                                'GST' => 'Visitor Course (GST)',
                                'FAM' => 'Familiarisation (FAM)',
                                'RST' => 'Roster Reentry (RST)',
                            ])
                            ->helperText('Type of training provided'),
                        
                        Forms\Components\Select::make('position')
                            ->required()
                            ->options([
                                'GND' => 'Ground',
                                'TWR' => 'Tower',
                                'APP' => 'Approach',
                                'CTR' => 'Centre',
                            ])
                            ->helperText('ATC position level'),
                        
                        Forms\Components\Select::make('mentor_group_id')
                            ->label('Mentor Group')
                            ->relationship('mentorGroup', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Which mentor group manages this course'),
                        
                        Forms\Components\Select::make('familiarisation_sector_id')
                            ->label('Familiarisation Sector')
                            ->relationship('familiarisationSector', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Required for FAM courses'),
                    ])->columns(2),

                Section::make('Rating Requirements')
                    ->schema([
                        Forms\Components\Select::make('min_rating')
                            ->label('Minimum Rating')
                            ->required()
                            ->options([
                                0 => 'None',
                                1 => 'OBS (Observer)',
                                2 => 'S1 (Tower Trainee)',
                                3 => 'S2 (Tower Controller)',
                                4 => 'S3 (Senior Student)',
                                5 => 'C1 (Enroute Controller)',
                                7 => 'C3 (Senior Controller)',
                                8 => 'I1 (Instructor)',
                                10 => 'I3 (Senior Instructor)',
                            ])
                            ->default(2),
                        
                        Forms\Components\Select::make('max_rating')
                            ->label('Maximum Rating')
                            ->required()
                            ->options([
                                0 => 'None',
                                1 => 'OBS (Observer)',
                                2 => 'S1 (Tower Trainee)',
                                3 => 'S2 (Tower Controller)',
                                4 => 'S3 (Senior Student)',
                                5 => 'C1 (Enroute Controller)',
                                7 => 'C3 (Senior Controller)',
                                8 => 'I1 (Instructor)',
                                10 => 'I3 (Senior Instructor)',
                            ])
                            ->default(3),
                    ])->columns(2),

                Section::make('Moodle Integration')
                    ->schema([
                        Forms\Components\TagsInput::make('moodle_course_ids')
                            ->label('Moodle Course IDs')
                            ->placeholder('Add course IDs')
                            ->helperText('Trainees will be enrolled in these Moodle courses')
                            ->separator(','),
                    ]),
            ]);
    }
}