<?php

namespace App\Filament\Resources\EndorsementActivities;

use App\Filament\Resources\EndorsementActivities\Pages\ListEndorsementActivities;
use App\Filament\Resources\EndorsementActivities\Pages\ViewEndorsementActivity;
use App\Filament\Resources\EndorsementActivities\Tables\EndorsementActivitiesTable;
use App\Models\EndorsementActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EndorsementActivityResource extends Resource
{
    protected static ?string $model = EndorsementActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?string $recordTitleAttribute = 'position';

    public static function table(Table $table): Table
    {
        return EndorsementActivitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEndorsementActivities::route('/'),
            'view' => ViewEndorsementActivity::route('/{record}'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Endorsements & Ratings';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationLabel(): string
    {
        return 'Endorsement Activities';
    }

    public static function canCreate(): bool
    {
        return false;
    }
}