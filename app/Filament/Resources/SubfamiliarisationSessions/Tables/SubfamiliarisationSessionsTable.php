<?php

namespace App\Filament\Resources\SubfamiliarisationSessions\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\UserSearch;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SubfamiliarisationSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Trainee')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record->user])),

                TextColumn::make('user.vatsim_id')
                    ->label('VATSIM ID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('subfamiliarisation.name')
                    ->label('Subfamiliarisation')
                    ->sortable(),

                TextColumn::make('mentor.name')
                    ->label('Signed Off By')
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Session Date')
                    ->date()
                    ->sortable(),

                IconColumn::make('moodle_override')
                    ->label('Moodle Overridden')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Logged')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subfamiliarisation_id')
                    ->label('Subfamiliarisation')
                    ->relationship('subfamiliarisation', 'name')
                    ->preload(),

                SelectFilter::make('user_id')
                    ->label('Trainee')
                    ->relationship('user', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->getSearchResultsUsing(UserSearch::callback())
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('moodle_override')
                    ->label('Moodle Overridden'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('completed_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 200]);
    }
}
