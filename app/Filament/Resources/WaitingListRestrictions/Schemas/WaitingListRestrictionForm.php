<?php

namespace App\Filament\Resources\WaitingListRestrictions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

class WaitingListRestrictionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ' (' . $record->vatsim_id . ')')
                    ->searchable(['first_name', 'last_name', 'vatsim_id'])
                    ->required(),

                Select::make('type')
                    ->options([
                        'RTG' => 'Rating (RTG)',
                        'EDMT' => 'Endorsement (EDMT)',
                        'GST' => 'Visitor (GST)',
                        'FAM' => 'Familiarisation (FAM)',
                        'RST' => 'Roster Reentry (RST)',
                    ])
                    ->required(),

                DatePicker::make('expires_at')
                    ->nullable(),
            ]);
    }
}
