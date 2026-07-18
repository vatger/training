<?php

namespace App\Filament\Resources\Examiners\Schemas;

use App\Models\Examiner;
use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Schemas\Components\Section;

class ExaminerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Examiner Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'first_name')
                            ->getSearchResultsUsing(\App\Filament\Support\UserSearch::callback())
                            ->getOptionLabelFromRecordUsing(\App\Filament\Support\UserSearch::optionLabel())
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('callsign')
                            ->label('Callsign')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('ATDGERXYZ'),

                        Forms\Components\CheckboxList::make('positions')
                            ->label('Authorized Positions')
                            ->options(Examiner::getPositionOptions())
                            ->required()
                            ->columns(3),
                    ])->columns(2),
            ]);
    }
}