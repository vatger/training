<?php

namespace App\Filament\Resources\LeadingMentors\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadingMentorsTable
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

                TextColumn::make('fir')
                    ->label('FIR')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Assigned At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('fir')
                    ->options([
                        'EDGG' => 'EDGG',
                        'EDMM' => 'EDMM',
                        'EDWW' => 'EDWW',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
