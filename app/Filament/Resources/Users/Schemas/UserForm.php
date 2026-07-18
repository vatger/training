<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;

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
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->disabled()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('VATSIM Details')
                    ->schema([
                        Forms\Components\TextInput::make('subdivision')
                            ->label('Subdivision')
                            ->maxLength(10),

                        Forms\Components\Select::make('rating')
                            ->label('ATC Rating')
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
                                11 => 'SUP (Supervisor)',
                                12 => 'ADM (Administrator)',
                            ]),

                        Forms\Components\DateTimePicker::make('last_rating_change')
                            ->label('Last Rating Change')
                            ->displayFormat('Y-m-d H:i')
                            ->seconds(false)
                            ->disabled(fn ($livewire) => !$livewire->ratingChangeUnlocked)
                            ->dehydrated()
                            ->hintAction(
                                Action::make('unlock_rating_change')
                                    ->label('Unlock to edit')
                                    ->icon('heroicon-o-lock-closed')
                                    ->color('warning')
                                    ->requiresConfirmation()
                                    ->modalHeading('Edit Last Rating Change')
                                    ->modalDescription('The last rating change date directly affects course access and waiting list eligibility. Are you sure you want to edit this field?')
                                    ->modalSubmitActionLabel('Yes, unlock')
                                    ->action(fn ($livewire) => $livewire->unlockRatingChange())
                                    ->hidden(fn ($livewire) => $livewire->ratingChangeUnlocked)
                            ),

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
                            ->modalDescription('System permissions grant elevated access across the entire platform. Are you sure you want to edit these settings?')
                            ->modalSubmitActionLabel('Yes, unlock')
                            ->action(fn ($livewire) => $livewire->unlockSystemPermissions())
                            ->hidden(fn ($livewire) => $livewire->systemPermissionsUnlocked),
                    ])
                    ->schema([
                        Forms\Components\Toggle::make('is_staff')
                            ->label('Staff Member')
                            ->helperText('Has access to staff features')
                            ->disabled(fn ($livewire) => !$livewire->systemPermissionsUnlocked)
                            ->dehydrated(),

                        Forms\Components\Toggle::make('is_superuser')
                            ->label('Superuser')
                            ->helperText('Has full system access')
                            ->disabled(fn ($livewire) => !$livewire->systemPermissionsUnlocked)
                            ->dehydrated(),

                        Forms\Components\Toggle::make('is_admin')
                            ->label('Admin Account')
                            ->helperText('Non-VATSIM admin account for development/emergency access')
                            ->disabled(fn ($livewire) => !$livewire->systemPermissionsUnlocked)
                            ->dehydrated(),
                    ])->columns(3),

                Section::make('Roles & Permissions')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->helperText('Assign mentor or leadership roles'),

                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Direct Permissions')
                            ->helperText('Permissions granted directly to this user, in addition to any role-based permissions.')
                            ->relationship(
                                'permissions',
                                'name',
                                fn ($query) => $query->orderBy('name')
                            )
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                $parts = explode('.', $record->name);
                                if (count($parts) >= 3) {
                                    $resource = \Illuminate\Support\Str::title(str_replace('_', ' ', $parts[1]));
                                    $action = \Illuminate\Support\Str::title($parts[2]);
                                    return "{$resource} — {$action}";
                                }
                                return \Illuminate\Support\Str::title(str_replace(['.', '_'], ' ', $record->name));
                            })
                            ->searchable()
                            ->columns(2)
                            ->gridDirection('row'),
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

                        Placeholder::make('rating_upgrade_pending')
                            ->label('Rating Upgrade Pending')
                            ->content(fn ($record) => $record?->rating_upgrade_pending ? 'Yes' : 'No'),

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
