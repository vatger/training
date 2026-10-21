<?php

namespace App\Filament\Resources\SubfamiliarisationSessions\Schemas;

use App\Filament\Support\UserSearch;
use App\Models\Subfamiliarisation;
use App\Models\User;
use App\Services\SubfamiliarisationService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SubfamiliarisationSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Practical Session')
                    ->description('Records that a trainee has completed the practical session required to control both sectors of a subfamiliarisation while holding only one of the underlying familiarisations.')
                    ->schema([
                        Forms\Components\Select::make('subfamiliarisation_id')
                            ->label('Subfamiliarisation')
                            ->relationship('subfamiliarisation', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->default(fn () => Subfamiliarisation::count() === 1 ? Subfamiliarisation::first()->id : null),

                        Forms\Components\Select::make('user_id')
                            ->label('Trainee')
                            ->relationship('user', 'first_name')
                            ->getSearchResultsUsing(UserSearch::callback())
                            ->getOptionLabelFromRecordUsing(UserSearch::optionLabel())
                            ->searchable()
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('mentor_id')
                            ->label('Signed Off By')
                            ->relationship('mentor', 'first_name')
                            ->getSearchResultsUsing(UserSearch::callback())
                            ->getOptionLabelFromRecordUsing(UserSearch::optionLabel())
                            ->searchable()
                            ->default(fn () => Filament::auth()->user()?->id)
                            ->disabled(fn () => ! static::isAdminOrSuperuser())
                            ->dehydrated()
                            ->required(),

                        Forms\Components\DatePicker::make('completed_at')
                            ->label('Session Date')
                            ->default(now())
                            ->required(),

                        Forms\Components\Placeholder::make('moodle_status')
                            ->label('Moodle Completion Status')
                            ->columnSpanFull()
                            ->content(function (Get $get) {
                                $subfamiliarisation = Subfamiliarisation::find($get('subfamiliarisation_id'));
                                $trainee = User::find($get('user_id'));

                                if (! $subfamiliarisation || ! $trainee) {
                                    return 'Select a trainee and subfamiliarisation to check Moodle completion.';
                                }

                                $complete = app(SubfamiliarisationService::class)
                                    ->hasCompletedMoodleRequirements($trainee, $subfamiliarisation);

                                return $complete
                                    ? 'Complete — all required Moodle courses have been completed.'
                                    : 'Incomplete — not all required Moodle courses have been completed yet.';
                            }),

                        Forms\Components\Toggle::make('moodle_override')
                            ->label('Override Moodle Completion Check')
                            ->helperText('Allows logging this session even though the trainee has not completed all required Moodle courses. Admin/superuser only.')
                            ->visible(fn () => static::isAdminOrSuperuser())
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('remarks')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    private static function isAdminOrSuperuser(): bool
    {
        $user = Filament::auth()->user();

        return (bool) $user && ($user->is_superuser || $user->is_admin);
    }
}
