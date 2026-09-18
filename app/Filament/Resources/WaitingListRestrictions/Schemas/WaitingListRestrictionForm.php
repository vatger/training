<?php

namespace App\Filament\Resources\WaitingListRestrictions\Schemas;

use App\Filament\Support\UserSearch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class WaitingListRestrictionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'first_name')
                    ->getSearchResultsUsing(UserSearch::callback())
                    ->getOptionLabelFromRecordUsing(UserSearch::optionLabel())
                    ->searchable()
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
