<?php

namespace App\Filament\Resources\TrainingLogs\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Models\TrainingLog;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class TrainingLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('trainee.name')
                    ->label('Trainee')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record->trainee])),

                TextColumn::make('mentor.name')
                    ->label('Mentor')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->mentor?->name ?? '—')
                    ->url(fn ($record) => $record->mentor 
                        ? UserResource::getUrl('edit', ['record' => $record->mentor]) 
                        : null
            ),
                

                TextColumn::make('course.name')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->url(fn ($record) => CourseResource::getUrl('edit', ['record' => $record->course])),

                TextColumn::make('position')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        TrainingLog::TYPE_ONLINE => 'success',
                        TrainingLog::TYPE_SIM => 'warning',
                        TrainingLog::TYPE_LESSON => 'info',
                        TrainingLog::TYPE_CUSTOM => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        TrainingLog::TYPE_ONLINE => 'Online',
                        TrainingLog::TYPE_SIM => 'Sim',
                        TrainingLog::TYPE_LESSON => 'Lesson',
                        TrainingLog::TYPE_CUSTOM => 'Custom',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('session_duration')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('average_rating')
                    ->label('Avg Rating')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn ($state) => $state >= 3 ? 'success' : ($state >= 2 ? 'warning' : 'danger')),

                IconColumn::make('result')
                    ->label('Passed')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('trainee_id')
                    ->label('Trainee')
                    ->relationship('trainee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('mentor_id')
                    ->label('Mentor')
                    ->relationship('mentor', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('course')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('type')
                    ->options([
                        TrainingLog::TYPE_ONLINE => 'Online',
                        TrainingLog::TYPE_SIM => 'Sim',
                        TrainingLog::TYPE_LESSON => 'Lesson',
                        TrainingLog::TYPE_CUSTOM => 'Custom',
                    ])
                    ->multiple(),

                Filter::make('result')
                    ->label('Passed Sessions')
                    ->query(fn ($query) => $query->where('result', true)),

                Filter::make('failed')
                    ->label('Failed Sessions')
                    ->query(fn ($query) => $query->where('result', false)),

                Filter::make('session_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn ($query, $date) => $query->whereDate('session_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn ($query, $date) => $query->whereDate('session_date', '<=', $date),
                            );
                    }),
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
            ->defaultSort('session_date', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 200]);
    }
}