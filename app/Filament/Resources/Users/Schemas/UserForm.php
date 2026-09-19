<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Cpts\CptResource;
use App\Filament\Resources\EndorsementActivities\EndorsementActivityResource;
use App\Filament\Resources\FamiliarisationSectors\FamiliarisationSectorResource;
use App\Filament\Resources\TrainingLogs\TrainingLogResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\UserSearch;
use App\Models\ChiefOfTraining;
use App\Models\Course;
use App\Models\FamiliarisationSector;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('vatsim_id')
                            ->label('VATSIM ID')
                            ->disabled()
                            ->numeric(),
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->disabled()
                            ->maxLength(255)
                            ->helperText('Synced from VATSIM on login.'),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->disabled()
                            ->maxLength(255)
                            ->helperText('Synced from VATSIM on login.'),
                    ])->columns(2),

                Section::make('VATSIM Details')
                    ->schema([
                        Forms\Components\TextInput::make('subdivision')
                            ->label('Subdivision')
                            ->maxLength(10),

                        Select::make('rating')
                            ->label('ATC Rating')
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
                                11 => 'SUP (Supervisor)',
                                12 => 'ADM (Administrator)',
                            ]),

                        Forms\Components\DateTimePicker::make('last_rating_change')
                            ->label('Last Rating Change')
                            ->displayFormat('Y-m-d H:i')
                            ->seconds(false)
                            ->disabled(fn ($livewire) => ! $livewire->isFieldUnlocked('rating_change'))
                            ->dehydrated()
                            ->hintAction(fn ($livewire) => $livewire->makeUnlockAction(
                                'rating_change',
                                'Edit Last Rating Change',
                                'Affects course and waiting list eligibility.',
                                label: 'Unlock',
                            )),

                        Forms\Components\TextInput::make('solo_days_used')
                            ->label('Used Solo Days')
                            ->integer()
                            ->suffix('days'),
                    ])->columns(2),

                Section::make('System Permissions')
                    ->headerActions([
                        Action::make('unlock_system_permissions')
                            ->label('Unlock to edit')
                            ->icon('heroicon-o-lock-closed')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Edit System Permissions')
                            ->modalDescription('Grants elevated access.')
                            ->modalSubmitActionLabel('Yes, unlock')
                            ->action(fn ($livewire) => $livewire->unlockField('system_permissions'))
                            ->hidden(fn ($livewire) => $livewire->isFieldUnlocked('system_permissions')),
                    ])
                    ->schema([
                        Forms\Components\Toggle::make('is_staff')
                            ->label('Staff Member')
                            ->disabled(fn ($livewire) => ! $livewire->isFieldUnlocked('system_permissions'))
                            ->dehydrated(),

                        Forms\Components\Toggle::make('is_superuser')
                            ->label('Superuser')
                            ->disabled(fn ($livewire) => ! $livewire->isFieldUnlocked('system_permissions'))
                            ->dehydrated(),

                        Forms\Components\Toggle::make('is_admin')
                            ->label('Admin Account')
                            ->helperText('Not synced from VATSIM.')
                            ->disabled(fn ($livewire) => ! $livewire->isFieldUnlocked('system_permissions'))
                            ->dehydrated(),
                    ])->columns(3),

                Section::make('Roles & Permissions')
                    ->collapsed()
                    ->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload(),

                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Direct Permissions')
                            ->relationship(
                                'permissions',
                                'name',
                                fn ($query) => $query->orderBy('name')
                            )
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                $parts = explode('.', $record->name);
                                if (count($parts) >= 3) {
                                    $resource = Str::title(str_replace('_', ' ', $parts[1]));
                                    $action = Str::title($parts[2]);

                                    return "{$resource} — {$action}";
                                }

                                return Str::title(str_replace(['.', '_'], ' ', $record->name));
                            })
                            ->searchable()
                            ->columns(2)
                            ->gridDirection('row'),
                    ])->columns(1),

                Section::make('Active Course Enrollments')
                    ->collapsed()
                    ->headerActions([
                        Action::make('add_course_enrollment')
                            ->label('Add')
                            ->icon('heroicon-o-plus')
                            ->size('sm')
                            ->visible(fn ($livewire) => $livewire->record !== null)
                            ->schema([
                                Select::make('course_id')
                                    ->label('Course')
                                    ->options(fn () => Course::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                            ])
                            ->action(fn (array $data, $livewire) => $livewire->addCourseEnrollment((int) $data['course_id'])),

                    ])
                    ->schema([
                        RepeatableEntry::make('activeCourses')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record?->activeCourses()->with('mentorGroup')->get() ?? [])
                            ->table([
                                TableColumn::make('Course'),
                                TableColumn::make('Claimed By'),
                                TableColumn::make('Claimed At'),
                                TableColumn::make('Remarks'),
                                TableColumn::make(''),
                            ])
                            ->schema([
                                TextEntry::make('name')
                                    ->url(fn ($record) => CourseResource::getUrl('edit', ['record' => $record])),
                                TextEntry::make('pivot.claimed_by_mentor_id')
                                    ->label('Claimed By')
                                    ->formatStateUsing(fn ($state) => $state ? User::find($state)?->name : '—'),
                                TextEntry::make('pivot.claimed_at')->dateTime()->placeholder('—'),
                                TextEntry::make('pivot.remarks')->limit(50)->placeholder('—'),
                                Actions::make([
                                    ActionGroup::make([
                                        Action::make('edit')
                                            ->label('Edit')
                                            ->icon('heroicon-o-pencil')
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
                                            ->action(fn (array $data, $record, $livewire) => $livewire->updateCourseEnrollmentPivot($record->id, $data)),

                                        Action::make('remove')
                                            ->label('Remove')
                                            ->icon('heroicon-o-x-mark')
                                            ->requiresConfirmation()
                                            ->action(fn ($record, $livewire) => $livewire->removeCourseEnrollment($record->id)),
                                    ]),
                                ]),
                            ])
                            ->placeholder('No active course enrollments.'),
                    ])->columns(1),

                Section::make('Waiting List Entries')
                    ->collapsed()
                    ->headerActions([
                        Action::make('add_waiting_list_entry')
                            ->label('Add')
                            ->icon('heroicon-o-plus')
                            ->size('sm')
                            ->visible(fn ($livewire) => $livewire->record !== null)
                            ->schema([
                                Select::make('course_id')
                                    ->label('Course')
                                    ->options(fn () => Course::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                            ])
                            ->action(fn (array $data, $livewire) => $livewire->addWaitingListEntry((int) $data['course_id'])),

                    ])
                    ->schema([
                        RepeatableEntry::make('waitingListEntries')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record?->waitingListEntries()->with('course')->get() ?? [])
                            ->table([
                                TableColumn::make('Course'),
                                TableColumn::make('Position'),
                                TableColumn::make('Added'),
                                TableColumn::make(''),
                            ])
                            ->schema([
                                TextEntry::make('course.name')
                                    ->placeholder('—')
                                    ->url(fn ($record) => $record->course ? CourseResource::getUrl('edit', ['record' => $record->course]) : null),
                                TextEntry::make('position_in_queue')->label('Position')->badge()->color('info'),
                                TextEntry::make('date_added')->dateTime(),
                                Actions::make([
                                    ActionGroup::make([
                                        Action::make('edit')
                                            ->label('Edit')
                                            ->icon('heroicon-o-pencil')
                                            ->fillForm(fn ($record) => [
                                                'activity' => $record->activity,
                                                'remarks' => $record->remarks,
                                            ])
                                            ->schema([
                                                Forms\Components\TextInput::make('activity')->numeric()->suffix('h'),
                                                Forms\Components\Textarea::make('remarks')->rows(2),
                                            ])
                                            ->action(fn (array $data, $record, $livewire) => $livewire->updateWaitingListEntry($record->id, $data)),

                                        Action::make('remove')
                                            ->label('Remove')
                                            ->icon('heroicon-o-x-mark')
                                            ->requiresConfirmation()
                                            ->action(fn ($record, $livewire) => $livewire->removeWaitingListEntry($record->id)),
                                    ]),
                                ]),
                            ])
                            ->placeholder('Not on any waiting list.'),
                    ])->columns(1),

                Section::make('Endorsements')
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('endorsementActivities')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record?->endorsementActivities ?? [])
                            ->table([
                                TableColumn::make('Position'),
                                TableColumn::make('Activity'),
                                TableColumn::make('Status'),
                            ])
                            ->schema([
                                TextEntry::make('position')
                                    ->url(fn ($record) => EndorsementActivityResource::getUrl('edit', ['record' => $record])),
                                TextEntry::make('activity_hours')->suffix('h')->numeric(decimalPlaces: 1),
                                TextEntry::make('removal_date')
                                    ->label('Status')
                                    ->formatStateUsing(fn ($state) => $state ? 'In Removal' : 'Active')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'danger' : 'success'),
                            ])
                            ->placeholder('No endorsements on record. Synced from VatEUD — not editable here.'),
                    ])->columns(1),

                Section::make('Familiarisations')
                    ->collapsed()
                    ->headerActions([
                        Action::make('add_familiarisation')
                            ->label('Add')
                            ->icon('heroicon-o-plus')
                            ->size('sm')
                            ->visible(fn ($livewire) => $livewire->record !== null)
                            ->schema([
                                Select::make('sector_id')
                                    ->label('Sector')
                                    ->options(fn () => FamiliarisationSector::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                            ])
                            ->action(fn (array $data, $livewire) => $livewire->addFamiliarisation((int) $data['sector_id'])),

                    ])
                    ->schema([
                        RepeatableEntry::make('familiarisations')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record?->familiarisations()->with('sector')->get() ?? [])
                            ->table([
                                TableColumn::make('Sector'),
                                TableColumn::make('FIR'),
                                TableColumn::make(''),
                            ])
                            ->schema([
                                TextEntry::make('sector.name')
                                    ->placeholder('—')
                                    ->url(fn ($record) => $record->sector ? FamiliarisationSectorResource::getUrl('edit', ['record' => $record->sector]) : null),
                                TextEntry::make('sector.fir')->placeholder('—'),
                                Actions::make([
                                    ActionGroup::make([
                                        Action::make('remove')
                                            ->label('Remove')
                                            ->icon('heroicon-o-x-mark')
                                            ->requiresConfirmation()
                                            ->action(fn ($record, $livewire) => $livewire->removeFamiliarisation($record->id)),
                                    ]),
                                ]),
                            ])
                            ->placeholder('No familiarisations on record.'),
                    ])->columns(1),

                Section::make('Training Logs')
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('trainingLogs')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record?->trainingLogs()->with(['course', 'mentor'])->latest('session_date')->limit(10)->get() ?? [])
                            ->table([
                                TableColumn::make('Date'),
                                TableColumn::make('Course'),
                                TableColumn::make('Mentor'),
                            ])
                            ->schema([
                                TextEntry::make('session_date')->date()
                                    ->url(fn ($record) => TrainingLogResource::getUrl('edit', ['record' => $record])),
                                TextEntry::make('course.name')
                                    ->placeholder('—')
                                    ->url(fn ($record) => $record->course ? CourseResource::getUrl('edit', ['record' => $record->course]) : null),
                                TextEntry::make('mentor.name')
                                    ->placeholder('—')
                                    ->url(fn ($record) => $record->mentor ? UserResource::getUrl('edit', ['record' => $record->mentor]) : null),
                            ])
                            ->placeholder('No training logs on record.'),
                    ])->columns(1),

                Section::make('Chief of Training / Leading Mentor')
                    ->collapsed()
                    ->headerActions([
                        Action::make('add_chief_of_training_course')
                            ->label('Add CoT Course')
                            ->icon('heroicon-o-plus')
                            ->size('sm')
                            ->visible(fn ($livewire) => $livewire->record !== null)
                            ->schema([
                                Select::make('course_id')
                                    ->label('Course')
                                    ->options(fn () => Course::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                            ])
                            ->action(fn (array $data, $livewire) => $livewire->addChiefOfTrainingCourse((int) $data['course_id'])),

                        Action::make('add_leading_mentor_fir')
                            ->label('Add LM FIR')
                            ->icon('heroicon-o-plus')
                            ->size('sm')
                            ->visible(fn ($livewire) => $livewire->record !== null)
                            ->schema([
                                Select::make('fir')
                                    ->label('FIR')
                                    ->options([
                                        'EDGG' => 'EDGG (Langen)',
                                        'EDMM' => 'EDMM (München)',
                                        'EDWW' => 'EDWW (Bremen)',
                                    ])
                                    ->required(),
                            ])
                            ->action(fn (array $data, $livewire) => $livewire->addLeadingMentorFir($data['fir'])),
                    ])
                    ->schema([
                        RepeatableEntry::make('chiefOfTrainingCourses')
                            ->label('Chief of Training For')
                            ->state(fn ($record) => $record
                                ? ChiefOfTraining::with('course')->where('user_id', $record->id)->get()
                                : [])
                            ->table([
                                TableColumn::make('Course'),
                                TableColumn::make(''),
                            ])
                            ->schema([
                                TextEntry::make('course.name')
                                    ->url(fn ($record) => CourseResource::getUrl('edit', ['record' => $record->course])),
                                Actions::make([
                                    ActionGroup::make([
                                        Action::make('remove')
                                            ->label('Remove')
                                            ->icon('heroicon-o-x-mark')
                                            ->requiresConfirmation()
                                            ->action(fn ($record, $livewire) => $livewire->removeChiefOfTrainingCourse($record->id)),
                                    ]),
                                ]),
                            ])
                            ->placeholder('—'),

                        RepeatableEntry::make('leadingMentorFirs')
                            ->label('Leading Mentor For')
                            ->state(fn ($record) => $record?->leadingMentorFirs ?? [])
                            ->table([
                                TableColumn::make('FIR'),
                                TableColumn::make(''),
                            ])
                            ->schema([
                                TextEntry::make('fir'),
                                Actions::make([
                                    ActionGroup::make([
                                        Action::make('remove')
                                            ->label('Remove')
                                            ->icon('heroicon-o-x-mark')
                                            ->requiresConfirmation()
                                            ->action(fn ($record, $livewire) => $livewire->removeLeadingMentorFir($record->id)),
                                    ]),
                                ]),
                            ])
                            ->placeholder('—'),
                    ])->columns(2),

                Section::make('CPTs (as Trainee)')
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('cpts')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record?->cpts()->with(['course', 'examiner'])->latest('date')->limit(10)->get() ?? [])
                            ->table([
                                TableColumn::make('Date'),
                                TableColumn::make('Course'),
                                TableColumn::make('Examiner'),
                                TableColumn::make('Result'),
                            ])
                            ->schema([
                                TextEntry::make('date')->dateTime('Y-m-d H:i')
                                    ->url(fn ($record) => CptResource::getUrl('edit', ['record' => $record])),
                                TextEntry::make('course.name')
                                    ->placeholder('—')
                                    ->url(fn ($record) => $record->course ? CourseResource::getUrl('edit', ['record' => $record->course]) : null),
                                TextEntry::make('examiner.name')
                                    ->placeholder('Not assigned')
                                    ->url(fn ($record) => $record->examiner ? UserResource::getUrl('edit', ['record' => $record->examiner]) : null),
                                TextEntry::make('passed')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        true => 'Passed',
                                        false => 'Failed',
                                        null => 'Pending',
                                    })
                                    ->color(fn ($state) => match ($state) {
                                        true => 'success',
                                        false => 'danger',
                                        null => 'warning',
                                    }),
                            ])
                            ->placeholder('No CPTs on record.'),
                    ])->columns(1),

                Section::make('All User Data')
                    ->collapsed()
                    ->schema([
                        Placeholder::make('id')
                            ->label('Internal ID')
                            ->content(fn ($record) => $record?->id ?? '—'),

                        Placeholder::make('email')
                            ->label('Email Address')
                            ->content(fn ($record) => $record?->email ?? '—'),

                        Placeholder::make('last_known_rating')
                            ->label('Last Known Rating')
                            ->content(fn ($record) => $record?->last_known_rating ?? '—'),

                        Placeholder::make('rating_upgraded_at')
                            ->label('Rating Upgraded At')
                            ->content(fn ($record) => $record?->rating_upgraded_at?->format('Y-m-d H:i') ?? '—'),

                        Forms\Components\Toggle::make('rating_upgrade_pending')
                            ->label('Rating Upgrade Pending')
                            ->disabled(fn ($livewire) => ! $livewire->isFieldUnlocked('rating_upgrade_pending'))
                            ->dehydrated()
                            ->hintAction(fn ($livewire) => $livewire->makeUnlockAction(
                                'rating_upgrade_pending',
                                'Edit Rating Upgrade Pending',
                                'Normally managed automatically.',
                            )),

                        Placeholder::make('created_at')
                            ->label('Account Created')
                            ->content(fn ($record) => $record?->created_at?->format('Y-m-d H:i') ?? '—'),

                        Placeholder::make('updated_at')
                            ->label('Last Updated')
                            ->content(fn ($record) => $record?->updated_at?->format('Y-m-d H:i') ?? '—'),
                    ])->columns(2),
            ]);
    }
}
