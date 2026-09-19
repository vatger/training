<?php

namespace App\Filament\Resources\WaitingListRestrictions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WaitingListRestrictionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.full_name')
                    ->label('User')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('user.vatsim_id')
                    ->label('VATSIM ID')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('type')
                    ->badge(),

                TextColumn::make('expires_at')
                    ->date(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'RTG' => 'Rating (RTG)',
                        'EDMT' => 'Endorsement (EDMT)',
                        'GST' => 'Visitor (GST)',
                        'FAM' => 'Familiarisation (FAM)',
                        'RST' => 'Roster Reentry (RST)',
                    ])
                    ->multiple(),

                Filter::make('active')
                    ->label('Currently Active')
                    ->query(fn ($query) => $query->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                    })),

                Filter::make('expired')
                    ->label('Expired')
                    ->query(fn ($query) => $query->whereNotNull('expires_at')->where('expires_at', '<', now())),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
