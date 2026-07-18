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
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vatsim_id')
                    ->label('VATSIM ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->sortable(),

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
            ->searchable()
            ->searchUsing(function (Builder $query, string $search): void {
                $search = trim($search);

                $query->where(function (Builder $query) use ($search): void {
                    // Pure number → starts-with match on VATSIM ID
                    if (ctype_digit($search)) {
                        $query->where('vatsim_id', 'like', "{$search}%");
                        return;
                    }

                    // Text → split on whitespace, every word must appear in first or last name
                    $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

                    foreach ($terms as $term) {
                        $query->where(function (Builder $query) use ($term): void {
                            $query->where('first_name', 'like', "%{$term}%")
                                ->orWhere('last_name', 'like', "%{$term}%");
                        });
                    }
                });
            })
            ->defaultSort('vatsim_id', 'asc')
            ->defaultPaginationPageOption(100)
            ->paginationPageOptions([25, 50, 100, 200, 500]);
    }
}