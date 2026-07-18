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
                            ->getSearchResultsUsing(\App\Filament\Support\UserSearch::callback())
                            ->getOptionLabelFromRecordUsing(\App\Filament\Support\UserSearch::optionLabel())
                            ->searchable()
                            ->required()
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