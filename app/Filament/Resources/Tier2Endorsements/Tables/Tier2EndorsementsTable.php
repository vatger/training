<?php

namespace App\Filament\Resources\Tier2Endorsements\Tables;

use App\Models\Tier2Endorsement;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class Tier2EndorsementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('position')
                    ->label('Position')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('moodle_course_id')
                    ->label('Moodle Course ID')
                    ->sortable()
                    ->placeholder('None'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('position')
                    ->options(fn () => Tier2Endorsement::query()
                        ->distinct()
                        ->orderBy('position')
                        ->pluck('position', 'position')
                        ->all())
                    ->multiple(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100]);
    }
}
