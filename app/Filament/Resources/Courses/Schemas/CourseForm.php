<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\UserSearch;
use App\Models\ChiefOfTraining;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

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
                            ->maxLength(100),

                        Forms\Components\TextInput::make('trainee_display_name')
                            ->label('Display Name for Trainees')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Airport Details')
                    ->schema([
                        Forms\Components\TextInput::make('airport_name')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('airport_icao')
                            ->label('Airport ICAO Code')
                            ->required()
                            ->maxLength(4)
                            ->placeholder('EDDF'),

                        Forms\Components\TextInput::make('solo_station')
                            ->label('Solo Station Callsign')
                            ->maxLength(15)
                            ->placeholder('EDDF_TWR'),
                    ])->columns(2),

                Section::make('Course Settings')
                    ->schema([
                        Select::make('type')
                            ->label('Course Type')
                            ->required()
                            ->live()
                            ->options([
                                'RTG' => 'Rating Course (RTG)',
                                'EDMT' => 'Endorsement Training (EDMT)',
                                'GST' => 'Visitor Course (GST)',
                                'FAM' => 'Familiarisation (FAM)',
                                'RST' => 'Roster Reentry (RST)',
                            ]),

                        Select::make('position')
                            ->required()
                            ->live()
                            ->options([
                                'GND' => 'Ground',
                                'TWR' => 'Tower',
                                'APP' => 'Approach',
                                'CTR' => 'Centre',
                            ]),

                        Select::make('mentor_group_id')
                            ->label('Mentor Group')
                            ->relationship('mentorGroup', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('familiarisation_sector_id')
                            ->label('Familiarisation Sector')
                            ->relationship('familiarisationSector', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('type') === 'FAM'),

                        Select::make('requiredFamiliarisationSectors')
                            ->label('Required Familiarisations')
                            ->relationship('requiredFamiliarisationSectors', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('type') === 'EDMT' && $get('position') === 'CTR'),
                    ])->columns(2),

                Section::make('Rating Requirements')
                    ->schema([
                        Select::make('min_rating')
                            ->label('Minimum Rating')
                            ->required()
                            ->options([
                                0 => 'None',
                                1 => 'OBS (Observer)',
                                2 => 'S1 (Tower Trainee)',
                                3 => 'S2 (Tower Controller)',
                                4 => 'S3 (Senior Student)',
                                5 => 'C1 (Enroute Controller)',
                                6 => 'C2 (Enroute Controller)',
                                7 => 'C3 (Senior Controller)',
                                8 => 'I1 (Instructor)',
                                9 => 'I2 (Instructor)',
                                10 => 'I3 (Senior Instructor)',
                                1000 => 'Unlimited',
                            ])
                            ->default(2),

                        Select::make('max_rating')
                            ->label('Maximum Rating')
                            ->required()
                            ->options([
                                0 => 'None',
                                1 => 'OBS (Observer)',
                                2 => 'S1 (Tower Trainee)',
                                3 => 'S2 (Tower Controller)',
                                4 => 'S3 (Senior Student)',
                                5 => 'C1 (Enroute Controller)',
                                6 => 'C2 (Enroute Controller)',
                                7 => 'C3 (Senior Controller)',
                                8 => 'I1 (Instructor)',
                                9 => 'I2 (Instructor)',
                                10 => 'I3 (Senior Instructor)',
                                1000 => 'Unlimited',
                            ])
                            ->default(3),
                    ])->columns(2),

                Section::make('Moodle Integration')
                    ->schema([
                        Forms\Components\TagsInput::make('moodle_course_ids')
                            ->label('Moodle Course IDs')
                            ->placeholder('Type ID and press Enter')
                            ->helperText('Trainees are auto-enrolled in these Moodle courses.'),
                    ]),

                Section::make('Endorsement Groups')
                    ->visible(fn (Get $get) => $get('type') === 'EDMT')
                    ->schema([
                        Forms\Components\TagsInput::make('endorsement_groups')
                            ->label('Endorsement Group Names')
                            ->placeholder('Type group name and press Enter'),
                    ]),

                Section::make('Mentors')
                    ->collapsed()
                    ->headerActions([
                        Action::make('add_mentor')
                            ->label('Add')
                            ->icon('heroicon-o-plus')
                            ->outlined()
                            ->size('sm')
                            ->visible(fn ($livewire) => $livewire->record !== null)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Mentor')
                                    ->getSearchResultsUsing(UserSearch::callback())
                                    ->getOptionLabelUsing(UserSearch::optionLabelById())
                                    ->searchable()
                                    ->required(),
                            ])
                            ->action(fn (array $data, $livewire) => $livewire->addMentor((int) $data['user_id'])),
                    ])
                    ->schema([
                        RepeatableEntry::make('mentors')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record?->mentors ?? [])
                            ->table([
                                TableColumn::make('Name'),
                                TableColumn::make('VATSIM ID'),
                                TableColumn::make(''),
                            ])
                            ->schema([
                                TextEntry::make('name')
                                    ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record])),
                                TextEntry::make('vatsim_id'),
                                Actions::make([
                                    ActionGroup::make([
                                        Action::make('remove')
                                            ->label('Remove')
                                            ->icon('heroicon-o-x-mark')
                                            ->outlined()
                                            ->requiresConfirmation()
                                            ->action(fn ($record, $livewire) => $livewire->removeMentor($record->id)),
                                    ]),
                                ]),
                            ])
                            ->placeholder('No mentors assigned yet.'),
                    ])->columns(1),

                Section::make('Trainees')
                    ->collapsed()
                    ->headerActions([
                        Action::make('add_trainee')
                            ->label('Add')
                            ->icon('heroicon-o-plus')
                            ->outlined()
                            ->size('sm')
                            ->visible(fn ($livewire) => $livewire->record !== null)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Trainee')
                                    ->getSearchResultsUsing(UserSearch::callback())
                                    ->getOptionLabelUsing(UserSearch::optionLabelById())
                                    ->searchable()
                                    ->required(),
                            ])
                            ->action(fn (array $data, $livewire) => $livewire->addTrainee((int) $data['user_id'])),

                    ])
                    ->schema([
                        RepeatableEntry::make('allTrainees')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record?->allTrainees()->latest('course_trainees.created_at')->limit(20)->get() ?? [])
                            ->table([
                                TableColumn::make('Name'),
                                TableColumn::make('Claimed By'),
                                TableColumn::make('Claimed At'),
                                TableColumn::make('Completed At'),
                                TableColumn::make(''),
                            ])
                            ->schema([
                                TextEntry::make('name')
                                    ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record])),
                                TextEntry::make('pivot.claimed_by_mentor_id')
                                    ->label('Claimed By')
                                    ->formatStateUsing(fn ($state) => $state ? User::find($state)?->name : '—'),
                                TextEntry::make('pivot.claimed_at')->dateTime()->placeholder('—'),
                                TextEntry::make('pivot.completed_at')->dateTime()->placeholder('Active'),
                                Actions::make([
                                    ActionGroup::make([
                                        Action::make('edit')
                                            ->label('Edit')
                                            ->icon('heroicon-o-pencil')
                                            ->outlined()
                                            ->fillForm(fn ($record) => [
                                                'claimed_by_mentor_id' => $record->pivot->claimed_by_mentor_id,
                                                'claimed_at' => $record->pivot->claimed_at,
                                                'completed_at' => $record->pivot->completed_at,
                                                'remarks' => $record->pivot->remarks,
                                            ])
                                            ->schema([
                                                Select::make('claimed_by_mentor_id')
                                                    ->label('Claimed By')
                                                    ->getSearchResultsUsing(UserSearch::callback())
                                                    ->getOptionLabelUsing(UserSearch::optionLabelById())
                                                    ->searchable(),
                                                Forms\Components\DateTimePicker::make('claimed_at'),
                                                Forms\Components\DateTimePicker::make('completed_at'),
                                                Forms\Components\Textarea::make('remarks')->rows(2),
                                            ])
                                            ->action(fn (array $data, $record, $livewire) => $livewire->updateTraineePivot($record->id, $data)),

                                        Action::make('remove')
                                            ->label('Remove')
                                            ->icon('heroicon-o-x-mark')
                                            ->outlined()
                                            ->requiresConfirmation()
                                            ->action(fn ($record, $livewire) => $livewire->removeTrainee($record->id)),
                                    ]),
                                ]),
                            ])
                            ->placeholder('No trainees on record yet.'),
                    ])->columns(1),

                Section::make('Chief of Training')
                    ->collapsed()
                    ->headerActions([
                        Action::make('add_chief_of_training')
                            ->label('Add')
                            ->icon('heroicon-o-plus')
                            ->outlined()
                            ->size('sm')
                            ->visible(fn ($livewire) => $livewire->record !== null)
                            ->schema([
                                Select::make('user_id')
                                    ->label('User')
                                    ->getSearchResultsUsing(UserSearch::callback())
                                    ->getOptionLabelUsing(UserSearch::optionLabelById())
                                    ->searchable()
                                    ->required(),
                            ])
                            ->action(fn (array $data, $livewire) => $livewire->addChiefOfTraining((int) $data['user_id'])),

                    ])
                    ->schema([
                        RepeatableEntry::make('chiefOfTrainings')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record
                                ? ChiefOfTraining::with('user')->where('course_id', $record->id)->get()
                                : [])
                            ->table([
                                TableColumn::make('Name'),
                                TableColumn::make('VATSIM ID'),
                                TableColumn::make(''),
                            ])
                            ->schema([
                                TextEntry::make('user.name')
                                    ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record->user])),
                                TextEntry::make('user.vatsim_id'),
                                Actions::make([
                                    ActionGroup::make([
                                        Action::make('remove')
                                            ->label('Remove')
                                            ->icon('heroicon-o-x-mark')
                                            ->outlined()
                                            ->requiresConfirmation()
                                            ->action(fn ($record, $livewire) => $livewire->removeChiefOfTraining($record->id)),
                                    ]),
                                ]),
                            ])
                            ->placeholder('No Chief of Training assigned yet.'),
                    ])->columns(1),
            ]);
    }
}
