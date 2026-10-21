<?php

namespace App\Filament\Resources\Subfamiliarisations\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubfamiliarisationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('primarySector.name')
                    ->label('Primary Sector')
                    ->sortable(),

                TextColumn::make('secondarySector.name')
                    ->label('Secondary Sector')
                    ->sortable(),

                TextColumn::make('moodle_course_ids')
                    ->label('Moodle Course IDs')
                    ->getStateUsing(fn ($record) => implode(', ', $record->moodle_course_ids)),

                TextColumn::make('sessions_count')
                    ->label('Sessions Logged')
                    ->counts('sessions')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name');
    }
}
