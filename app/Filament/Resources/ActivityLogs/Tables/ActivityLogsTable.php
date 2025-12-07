<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Enums\ActivityAction;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('action')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->getActionLabel())
                    ->color(fn ($record) => $record->getActionColor())
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->default('System')
                    ->url(fn ($record) => $record->user ? UserResource::getUrl('edit', ['record' => $record->user]) : null),

                TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('model_type')
                    ->label('Subject Type')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('model_id')
                    ->label('Subject ID')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Filter::make('action')
                    ->form([
                        \Filament\Forms\Components\Select::make('action')
                            ->label('Action Contains')
                            ->options(ActivityAction::getFilterOptions())
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['action'])) {
                            return $query->where(function (Builder $q) use ($data) {
                                foreach ($data['action'] as $action) {
                                    $q->orWhere('action', 'like', '%' . $action . '%');
                                }
                            });
                        }
                        return $query;
                    }),

                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('model_type')
                    ->label('Subject Type')
                    ->options([
                        'App\Models\Course' => 'Course',
                        'App\Models\User' => 'User',
                        'App\Models\WaitingListEntry' => 'Waiting List',
                        'App\Models\TrainingLog' => 'Training Log',
                        'App\Models\EndorsementActivity' => 'Endorsement',
                    ])
                    ->multiple(),

                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 200]);
    }
}