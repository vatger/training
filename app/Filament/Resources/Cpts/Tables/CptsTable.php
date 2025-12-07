<?php

namespace App\Filament\Resources\Cpts\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class CptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('trainee.name')
                    ->label('Trainee')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->url(fn ($record) => $record->trainee ? UserResource::getUrl('edit', ['record' => $record->trainee]) : null),

                TextColumn::make('course.name')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->url(fn ($record) => CourseResource::getUrl('edit', ['record' => $record->course])),

                TextColumn::make('date')
                    ->label('CPT Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('examiner.name')
                    ->label('Examiner')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->placeholder('Not assigned')
                    ->url(fn ($record) => $record->examiner ? UserResource::getUrl('edit', ['record' => $record->examiner]) : null),

                TextColumn::make('local.name')
                    ->label('Local Contact')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->placeholder('Not assigned')
                    ->url(fn ($record) => $record->local ? UserResource::getUrl('edit', ['record' => $record->local]) : null),

                IconColumn::make('confirmed')
                    ->label('Confirmed')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('log_uploaded')
                    ->label('Log Uploaded')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('passed')
                    ->label('Result')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        true => 'Passed',
                        false => 'Failed',
                        null => 'Pending',
                    })
                    ->color(fn ($state) => match($state) {
                        true => 'success',
                        false => 'danger',
                        null => 'warning',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('course')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('trainee_id')
                    ->label('Trainee')
                    ->relationship('trainee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('examiner_id')
                    ->label('Examiner')
                    ->relationship('examiner', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Filter::make('confirmed')
                    ->label('Confirmed Only')
                    ->query(fn ($query) => $query->where('confirmed', true)),

                Filter::make('pending')
                    ->label('Pending Result')
                    ->query(fn ($query) => $query->whereNull('passed')),

                Filter::make('passed')
                    ->label('Passed')
                    ->query(fn ($query) => $query->where('passed', true)),

                Filter::make('failed')
                    ->label('Failed')
                    ->query(fn ($query) => $query->where('passed', false)),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('date', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 200]);
    }
}