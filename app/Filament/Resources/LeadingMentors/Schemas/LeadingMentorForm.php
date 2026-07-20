<?php

namespace App\Filament\Resources\LeadingMentors\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class LeadingMentorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' ('.$record->vatsim_id.')')
                    ->searchable(['first_name', 'last_name', 'vatsim_id'])
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
