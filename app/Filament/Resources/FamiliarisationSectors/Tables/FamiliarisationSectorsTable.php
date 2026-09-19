<?php

namespace App\Filament\Resources\FamiliarisationSectors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FamiliarisationSectorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('fir')
                    ->label('FIR')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('familiarisations_count')
                    ->label('Active FAMs')
                    ->counts('familiarisations')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('courses_count')
                    ->label('Courses')
                    ->counts('courses')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('fir')
                    ->label('FIR')
                    ->options([
                        'EDGG' => 'EDGG',
                        'EDMM' => 'EDMM',
                        'EDWW' => 'EDWW',
                    ])
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
