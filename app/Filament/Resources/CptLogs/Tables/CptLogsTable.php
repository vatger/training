<?php

namespace App\Filament\Resources\CptLogs\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Cpts\CptResource;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class CptLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('cpt.trainee.name')
                    ->label('Trainee')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->url(fn ($record) => $record->cpt?->trainee 
                        ? UserResource::getUrl('edit', ['record' => $record->cpt->trainee]) 
                        : null
                    ),

                TextColumn::make('cpt.course.name')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('cpt.course.solo_station')
                    ->label('Position')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded By')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->url(fn ($record) => $record->uploadedBy 
                        ? UserResource::getUrl('edit', ['record' => $record->uploadedBy]) 
                        : null
                    ),

                TextColumn::make('file_name')
                    ->label('File')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($state) => $state),

                TextColumn::make('cpt.date')
                    ->label('CPT Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Uploaded At')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                TextColumn::make('cpt.passed')
                    ->label('CPT Result')
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
            ])
            ->filters([
                SelectFilter::make('cpt.trainee_id')
                    ->label('Trainee')
                    ->relationship('cpt.trainee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('uploaded_by_id')
                    ->label('Uploaded By')
                    ->relationship('uploadedBy', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('cpt.course_id')
                    ->label('Course')
                    ->relationship('cpt.course', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Filter::make('passed')
                    ->label('Passed CPTs')
                    ->query(fn ($query) => $query->whereHas('cpt', fn ($q) => $q->where('passed', true))),

                Filter::make('failed')
                    ->label('Failed CPTs')
                    ->query(fn ($query) => $query->whereHas('cpt', fn ($q) => $q->where('passed', false))),

                Filter::make('pending')
                    ->label('Pending CPTs')
                    ->query(fn ($query) => $query->whereHas('cpt', fn ($q) => $q->whereNull('passed'))),

                Filter::make('created_at')
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
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make()
                    ->before(function ($record) {
                        if (Storage::disk('private')->exists($record->log_file)) {
                            Storage::disk('private')->delete($record->log_file);
                        } elseif (Storage::disk('public')->exists($record->log_file)) {
                            Storage::disk('public')->delete($record->log_file);
                        }
                        
                        $cpt = $record->cpt;
                        if ($cpt && $cpt->logs()->count() === 1) {
                            $cpt->update(['log_uploaded' => false]);
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 200]);
    }
}