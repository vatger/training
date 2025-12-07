<?php

namespace App\Filament\Resources\Familiarisations\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Schemas\Components\Section;

class FamiliarisationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Familiarisation Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->vatsim_id})")
                            ->searchable(['first_name', 'last_name', 'vatsim_id'])
                            ->required()
                            ->preload()
                            ->helperText('Select the user to grant familiarisation'),

                        Forms\Components\Select::make('familiarisation_sector_id')
                            ->label('Sector')
                            ->relationship('sector', 'name')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->helperText('Select the familiarisation sector'),
                    ])->columns(2),
            ]);
    }
}