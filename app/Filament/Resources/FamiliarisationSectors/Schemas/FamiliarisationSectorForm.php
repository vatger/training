<?php

namespace App\Filament\Resources\FamiliarisationSectors\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Schemas\Components\Section;

class FamiliarisationSectorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sector Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('fir')
                            ->label('FIR')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('EDGG, EDMM, EDWW'),
                    ])->columns(2),
            ]);
    }
}