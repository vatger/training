<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Permission;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('VATSIM Details')
                    ->schema([
                        Forms\Components\Select::make('subdivision')
                            ->label('Subdivision')
                            ->options([
                                'GER' => 'Germany',
                                'USA' => 'United States',
                                'GBR' => 'United Kingdom',
                                'FRA' => 'France',
                                'ITA' => 'Italy',
                                'ESP' => 'Spain',
                                'NLD' => 'Netherlands',
                                'BEL' => 'Belgium',
                                'AUT' => 'Austria',
                                'CHE' => 'Switzerland',
                                'POL' => 'Poland',
                                'CZE' => 'Czech Republic',
                                'DNK' => 'Denmark',
                                'SWE' => 'Sweden',
                                'NOR' => 'Norway',
                                'FIN' => 'Finland',
                            ])
                            ->searchable()
                            ->placeholder('Select subdivision'),

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
                            ->seconds(false),

                        Forms\Components\TextInput::make('solo_days_used')
                            ->label('Used Solo Days')
                            ->integer()
                            ->suffix('days'),
                    ])->columns(2),

                Section::make('System Permissions')
                    ->schema([
                        Forms\Components\Toggle::make('is_staff')
                            ->label('Staff Member')
                            ->helperText('Has access to staff features'),

                        Forms\Components\Toggle::make('is_superuser')
                            ->label('Superuser')
                            ->helperText('Has full system access'),

                        Forms\Components\Toggle::make('is_admin')
                            ->label('Admin Account')
                            ->helperText('Non-VATSIM admin account for development/emergency access'),
                    ])->columns(3),

                Section::make('Roles & Permissions')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->helperText('Assign mentor or leadership roles'),

                        Forms\Components\CheckboxList::make('permission_ids')
                            ->label('Direct Permissions')
                            ->helperText('Grant specific permissions to this user (in addition to role-based permissions)')
                            ->options(function () {
                                return Permission::query()
                                    ->orderBy('group')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($permission) {
                                        $label = $permission->group
                                            ? "[{$permission->group}] {$permission->name}"
                                            : $permission->name;

                                        return [$permission->id => $label];
                                    });
                            })
                            ->columns(2)
                            ->gridDirection('row')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record) {
                                    $component->state($record->permissions->pluck('id')->toArray());
                                }
                            }),
                    ])->columns(1),
            ]);
    }
}
