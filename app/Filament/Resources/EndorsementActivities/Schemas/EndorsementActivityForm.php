<?php

namespace App\Filament\Resources\EndorsementActivities\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class EndorsementActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Endorsement Information')
                    ->schema([
                        Forms\Components\TextInput::make('vatsim_id')
                            ->label('VATSIM ID')
                            ->required()
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('position')
                            ->label('Position')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('endorsement_id')
                            ->label('Endorsement ID')
                            ->numeric()
                            ->helperText('VatEUD endorsement ID'),
                    ])->columns(2),

                Section::make('Activity Details')
                    ->schema([
                        Forms\Components\TextInput::make('activity_minutes')
                            ->label('Activity (minutes)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('min')
                            ->helperText('Total activity time in minutes'),

                        Forms\Components\DateTimePicker::make('last_activity_date')
                            ->label('Last Activity Date')
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('last_updated')
                            ->label('Last Updated')
                            ->seconds(false)
                            ->helperText('Last time activity was synced from VatEUD'),

                        Forms\Components\DateTimePicker::make('created_at_vateud')
                            ->label('Created at VatEUD')
                            ->seconds(false)
                            ->helperText('When endorsement was added in VatEUD'),
                    ])->columns(2),

                Section::make('Removal Status')
                    ->schema([
                        Forms\Components\DatePicker::make('removal_date')
                            ->label('Removal Date')
                            ->helperText('Date when endorsement will be removed if not reactivated'),

                        Forms\Components\Toggle::make('removal_notified')
                            ->label('Removal Notification Sent')
                            ->helperText('Has the user been notified about the removal?'),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }
}