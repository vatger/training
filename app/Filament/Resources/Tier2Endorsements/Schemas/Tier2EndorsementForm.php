<?php

namespace App\Filament\Resources\Tier2Endorsements\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Schemas\Components\Section;

class Tier2EndorsementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tier 2 Endorsement Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Display name for this endorsement'),

                        Forms\Components\TextInput::make('position')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Position code (e.g., EDXX_AFIS, EDWW_CTR)'),

                        Forms\Components\TextInput::make('moodle_course_id')
                            ->label('Moodle Course ID')
                            ->numeric()
                            ->helperText('Optional: Link to Moodle course for theory'),
                    ])->columns(2),
            ]);
    }
}