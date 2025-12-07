<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vatsim_id')
                    ->label('VATSIM ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subdivision')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('rating')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn(int $state): string => match ($state) {
                        0 => 'None',
                        1 => 'OBS',
                        2 => 'S1',
                        3 => 'S2',
                        4 => 'S3',
                        5 => 'C1',
                        7 => 'C3',
                        8 => 'I1',
                        10 => 'I3',
                        11 => 'SUP',
                        12 => 'ADM',
                        default => "Unknown ($state)",
                    })
                    ->sortable(),

                IconColumn::make('is_staff')
                    ->label('Staff')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_superuser')
                    ->label('Superuser')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(',')
                    ->color('warning'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_rating_change')
                    ->label('Last Rating Change')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_staff')
                    ->label('Staff Members'),

                TernaryFilter::make('is_superuser')
                    ->label('Superusers'),

                TernaryFilter::make('is_admin')
                    ->label('Admin Accounts'),

                SelectFilter::make('subdivision')
                    ->options([
                        'GER' => 'Germany',
                        'USA' => 'United States',
                        'GBR' => 'United Kingdom',
                        'FRA' => 'France',
                        'ITA' => 'Italy',
                        'ESP' => 'Spain',
                        'NLD' => 'Netherlands',
                        'BEL' => 'Belgium',
                        'AUT' => 'Austria',
                        'CHE' => 'Switzerland',
                    ])
                    ->multiple(),

                SelectFilter::make('rating')
                    ->label('ATC Rating')
                    ->options([
                        0 => 'None',
                        1 => 'OBS',
                        2 => 'S1',
                        3 => 'S2',
                        4 => 'S3',
                        5 => 'C1',
                        7 => 'C3',
                        8 => 'I1',
                        10 => 'I3',
                        11 => 'SUP',
                        12 => 'ADM',
                    ])
                    ->multiple(),

                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('vatsim_id', 'asc')
            ->defaultPaginationPageOption(100)
            ->paginationPageOptions([25, 50, 100, 200, 500]);
    }
}