<?php

namespace App\Filament\Resources\Familiarisations;

use App\Filament\Resources\Familiarisations\Pages\ListFamiliarisations;
use App\Filament\Resources\Familiarisations\Pages\CreateFamiliarisation;
use App\Filament\Resources\Familiarisations\Pages\EditFamiliarisation;
use App\Filament\Resources\Familiarisations\Schemas\FamiliarisationForm;
use App\Filament\Resources\Familiarisations\Tables\FamiliarisationsTable;
use App\Models\Familiarisation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FamiliarisationResource extends Resource
{
    protected static ?string $model = Familiarisation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeEuropeAfrica;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return FamiliarisationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FamiliarisationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamiliarisations::route('/'),
            'create' => CreateFamiliarisation::route('/create'),
            'edit' => EditFamiliarisation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Endorsements & Ratings';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function getNavigationLabel(): string
    {
        return 'Familiarisations';
    }
}