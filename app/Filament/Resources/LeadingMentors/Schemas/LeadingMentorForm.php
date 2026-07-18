<?php

namespace App\Filament\Resources\LeadingMentors\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;

class LeadingMentorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'first_name')
                    ->getSearchResultsUsing(\App\Filament\Support\UserSearch::callback())
                    ->getOptionLabelFromRecordUsing(\App\Filament\Support\UserSearch::optionLabel())
                    ->searchable()
                    ->required()
                    ->helperText('Select the user who will be Leading Mentor for this FIR'),
                
                Forms\Components\Select::make('fir')
                    ->label('FIR')
                    ->options([
                        'EDGG' => 'EDGG (Langen)',
                        'EDMM' => 'EDMM (München)',
                        'EDWW' => 'EDWW (Bremen)',
                    ])
                    ->required()
                    ->helperText('Select the FIR this user will manage'),
            ]);
    }
}