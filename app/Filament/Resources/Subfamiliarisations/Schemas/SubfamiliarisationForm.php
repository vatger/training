<?php

namespace App\Filament\Resources\Subfamiliarisations\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubfamiliarisationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subfamiliarisation')
                    ->description('Defines a pair of familiarisation sectors that may be controlled together. A user who holds both sectors normally may always do so. A user who holds only one may also do so once they meet the extra requirements below, tracked as sessions on this subfamiliarisation.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->helperText('e.g. "EMS / NH Maastricht Combined Control"')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('primary_sector_id')
                            ->label('Primary Sector')
                            ->relationship('primarySector', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->different('secondary_sector_id'),

                        Forms\Components\Select::make('secondary_sector_id')
                            ->label('Secondary Sector')
                            ->relationship('secondarySector', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->different('primary_sector_id'),

                        Forms\Components\TagsInput::make('moodle_course_ids')
                            ->label('Required Moodle Course IDs')
                            ->placeholder('Type ID and press Enter')
                            ->helperText('All of these courses must be completed for a user holding only one sector to qualify for combined control. Any number of courses can be required.')
                            ->columnSpanFull()
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
