<?php

namespace App\Filament\Resources\Courses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('airport_icao')
                    ->label('Airport')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'RTG' => 'success',
                        'EDMT' => 'warning',
                        'GST' => 'info',
                        'FAM' => 'purple',
                        'RST' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'RTG' => 'Rating',
                        'EDMT' => 'Endorsement',
                        'GST' => 'Visitor',
                        'FAM' => 'Familiarisation',
                        'RST' => 'Roster Reentry',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('position')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'GND' => 'Ground',
                        'TWR' => 'Tower',
                        'APP' => 'Approach',
                        'CTR' => 'Centre',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('mentorGroup.name')
                    ->label('Mentor Group')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('min_rating')
                    ->label('Min Rating')
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        0 => 'None',
                        1 => 'OBS',
                        2 => 'S1',
                        3 => 'S2',
                        4 => 'S3',
                        5 => 'C1',
                        7 => 'C3',
                        8 => 'I1',
                        10 => 'I3',
                        default => (string) $state,
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('max_rating')
                    ->label('Max Rating')
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        0 => 'None',
                        1 => 'OBS',
                        2 => 'S1',
                        3 => 'S2',
                        4 => 'S3',
                        5 => 'C1',
                        7 => 'C3',
                        8 => 'I1',
                        10 => 'I3',
                        default => (string) $state,
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('activeTrainees_count')
                    ->label('Active Trainees')
                    ->counts('activeTrainees')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('waitingListEntries_count')
                    ->label('Waiting List')
                    ->counts('waitingListEntries')
                    ->badge()
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'RTG' => 'Rating',
                        'EDMT' => 'Endorsement',
                        'GST' => 'Visitor',
                        'FAM' => 'Familiarisation',
                        'RST' => 'Roster Reentry',
                    ])
                    ->multiple(),

                SelectFilter::make('position')
                    ->options([
                        'GND' => 'Ground',
                        'TWR' => 'Tower',
                        'APP' => 'Approach',
                        'CTR' => 'Centre',
                    ])
                    ->multiple(),

                SelectFilter::make('mentor_group_id')
                    ->label('Mentor Group')
                    ->relationship('mentorGroup', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 200, 500]);
    }
}