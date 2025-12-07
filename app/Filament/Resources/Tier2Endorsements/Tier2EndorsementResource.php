<?php

namespace App\Filament\Resources\Tier2Endorsements;

use App\Filament\Resources\Tier2Endorsements\Pages\ListTier2Endorsements;
use App\Filament\Resources\Tier2Endorsements\Pages\CreateTier2Endorsement;
use App\Filament\Resources\Tier2Endorsements\Pages\EditTier2Endorsement;
use App\Filament\Resources\Tier2Endorsements\Schemas\Tier2EndorsementForm;
use App\Filament\Resources\Tier2Endorsements\Tables\Tier2EndorsementsTable;
use App\Models\Tier2Endorsement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class Tier2EndorsementResource extends Resource
{
    protected static ?string $model = Tier2Endorsement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return Tier2EndorsementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tier2EndorsementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTier2Endorsements::route('/'),
            'create' => CreateTier2Endorsement::route('/create'),
            'edit' => EditTier2Endorsement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Endorsements & Ratings';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationLabel(): string
    {
        return 'Tier 2 Endorsements';
    }
}