<?php

namespace App\Filament\Resources\GdprRemovalRequests;

use App\Filament\Resources\GdprRemovalRequests\Pages\ListGdprRemovalRequests;
use App\Filament\Resources\GdprRemovalRequests\Pages\ViewGdprRemovalRequest;
use App\Filament\Resources\GdprRemovalRequests\RelationManagers\ReviewItemsRelationManager;
use App\Models\GdprRemovalRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GdprRemovalRequestResource extends Resource
{
    protected static ?string $model = GdprRemovalRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function table(Table $table): Table
    {
        return $table
            // Open work first: reviews in progress, then awaiting approval, then finished requests. Newest first within each group.
            ->defaultSort(fn (Builder $query) => $query
                ->orderByRaw('CASE status WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [
                    GdprRemovalRequest::STATUS_APPROVED,
                    GdprRemovalRequest::STATUS_PENDING,
                ])
                ->orderByDesc('created_at'))
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'reviewItems',
                'reviewItems as open_review_items_count' => fn (Builder $q) => $q->whereNull('reviewed_at'),
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('vatsim_id')
                    ->label('VATSIM ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('Name')
                    ->placeholder('Removed')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        GdprRemovalRequest::STATUS_PENDING => 'Awaiting approval',
                        GdprRemovalRequest::STATUS_APPROVED => 'Review in progress',
                        GdprRemovalRequest::STATUS_CANCELLED => 'Cancelled',
                        default => 'Completed',
                    })
                    ->color(fn (string $state) => match ($state) {
                        GdprRemovalRequest::STATUS_PENDING => 'warning',
                        GdprRemovalRequest::STATUS_APPROVED => 'info',
                        GdprRemovalRequest::STATUS_CANCELLED => 'gray',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('open_review_items_count')
                    ->label('Open reviews')
                    ->formatStateUsing(fn ($state, GdprRemovalRequest $record) => $record->review_items_count ? "{$state} / {$record->review_items_count}" : '-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved by')
                    ->placeholder(fn (GdprRemovalRequest $record) => $record->approved_at ? 'Automatic' : '-'),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        GdprRemovalRequest::STATUS_PENDING => 'Awaiting approval',
                        GdprRemovalRequest::STATUS_APPROVED => 'Review in progress',
                        GdprRemovalRequest::STATUS_COMPLETED => 'Completed',
                        GdprRemovalRequest::STATUS_CANCELLED => 'Cancelled',
                    ]),
            ])
            ->recordUrl(fn (GdprRemovalRequest $record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            ReviewItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGdprRemovalRequests::route('/'),
            'view' => ViewGdprRemovalRequest::route('/{record}'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'GDPR Requests';
    }

    public static function getModelLabel(): string
    {
        return 'GDPR request';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System & Administration';
    }

    public static function getNavigationBadge(): ?string
    {
        $open = GdprRemovalRequest::open()->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
