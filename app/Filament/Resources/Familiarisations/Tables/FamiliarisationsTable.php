<?php

namespace App\Filament\Resources\Familiarisations\Tables;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class FamiliarisationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record->user])),

                TextColumn::make('user.vatsim_id')
                    ->label('VATSIM ID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('sector.name')
                    ->label('Sector')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sector.fir')
                    ->label('FIR')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sector')
                    ->relationship('sector', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('fir')
                    ->label('FIR')
                    ->options(function () {
                        return \App\Models\FamiliarisationSector::query()
                            ->distinct()
                            ->pluck('fir', 'fir')
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('sector', function ($q) use ($data) {
                                $q->where('fir', $data['value']);
                            });
                        }
                    }),

                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 200]);
    }
}