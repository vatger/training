<?php

namespace App\Filament\Resources\EndorsementActivities\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class EndorsementActivitiesTable
{
    public static function configure(Table $table): Table
    {
        $minActivityMinutes = config('services.vateud.min_activity_minutes', 180);

        return $table
            ->columns([
                TextColumn::make('vatsim_id')
                    ->label('VATSIM ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('position')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('activity_hours')
                    ->label('Activity')
                    ->suffix('h')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->color(fn ($record) => match($record->status) {
                        'active' => 'success',
                        'warning' => 'warning',
                        'removal' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('progress')
                    ->label('Progress')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'warning' => 'warning',
                        'removal' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Active',
                        'warning' => 'Low Activity',
                        'removal' => 'In Removal',
                        default => ucfirst($state),
                    }),

                TextColumn::make('last_activity_date')
                    ->label('Last Activity')
                    ->date()
                    ->sortable()
                    ->since(),

                TextColumn::make('removal_date')
                    ->label('Removal Date')
                    ->date()
                    ->sortable()
                    ->placeholder('-')
                    ->color('danger'),

                TextColumn::make('last_updated')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at_vateud')
                    ->label('Added to VatEUD')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'warning' => 'Low Activity',
                        'removal' => 'In Removal',
                    ])
                    ->query(function ($query, array $data) use ($minActivityMinutes) {
                        if (!empty($data['value'])) {
                            $status = $data['value'];
                            
                            if ($status === 'active') {
                                $query->where('activity_minutes', '>=', $minActivityMinutes)
                                    ->whereNull('removal_date');
                            } elseif ($status === 'warning') {
                                $query->where('activity_minutes', '<', $minActivityMinutes)
                                    ->whereNull('removal_date');
                            } elseif ($status === 'removal') {
                                $query->whereNotNull('removal_date');
                            }
                        }
                    }),

                Filter::make('low_activity')
                    ->label('Low Activity')
                    ->query(fn ($query) => $query->where('activity_minutes', '<', $minActivityMinutes)),

                Filter::make('marked_for_removal')
                    ->label('Marked for Removal')
                    ->query(fn ($query) => $query->whereNotNull('removal_date')),

                Filter::make('needs_notification')
                    ->label('Needs Notification')
                    ->query(fn ($query) => $query->whereNotNull('removal_date')
                        ->where('removal_notified', false)),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('activity_minutes', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 200]);
    }
}