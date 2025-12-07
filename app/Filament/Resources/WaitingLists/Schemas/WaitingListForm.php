<?php

namespace App\Filament\Resources\WaitingLists\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Schemas\Components\Section;

class WaitingListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Waiting List Entry')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->vatsim_id})")
                            ->searchable(['first_name', 'last_name', 'vatsim_id'])
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'name')
                            ->searchable()
                            ->required()
                            ->preload(),

                        Forms\Components\DateTimePicker::make('date_added')
                            ->label('Date Added')
                            ->required()
                            ->default(now())
                            ->seconds(false),

                        Forms\Components\TextInput::make('activity')
                            ->label('Activity Hours')
                            ->numeric()
                            ->default(0)
                            ->suffix('hours')
                            ->helperText('VATSIM activity hours'),

                        Forms\Components\DateTimePicker::make('hours_updated')
                            ->label('Activity Last Updated')
                            ->default(now()->subYears(25))
                            ->seconds(false),

                        Forms\Components\Textarea::make('remarks')
                            ->label('Remarks')
                            ->columnSpanFull()
                            ->rows(3)
                            ->helperText('Internal notes about this trainee'),
                    ])->columns(2),
            ]);
    }
}