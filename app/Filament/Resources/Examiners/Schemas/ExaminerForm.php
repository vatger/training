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
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->vatsim_id})")
                            ->searchable(['first_name', 'last_name', 'vatsim_id'])
                            ->required()
                            ->preload(),

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