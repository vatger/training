<?php

namespace App\Filament\Resources\ChiefOfTrainings\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChiefOfTrainingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('user.vatsim_id')
                    ->label('VATSIM ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course.name')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course.position')
                    ->label('Position')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Assigned At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
