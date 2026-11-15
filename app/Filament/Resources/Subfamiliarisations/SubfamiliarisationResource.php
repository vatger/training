<?php

namespace App\Filament\Resources\Subfamiliarisations;

use App\Filament\Clusters\FamiliarisationCluster;
use App\Filament\Resources\Subfamiliarisations\Pages\CreateSubfamiliarisation;
use App\Filament\Resources\Subfamiliarisations\Pages\EditSubfamiliarisation;
use App\Filament\Resources\Subfamiliarisations\Pages\ListSubfamiliarisations;
use App\Filament\Resources\Subfamiliarisations\Schemas\SubfamiliarisationForm;
use App\Filament\Resources\Subfamiliarisations\Tables\SubfamiliarisationsTable;
use App\Models\Subfamiliarisation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubfamiliarisationResource extends Resource
{
    protected static ?string $model = Subfamiliarisation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $cluster = FamiliarisationCluster::class;

    public static function form(Schema $schema): Schema
    {
        return SubfamiliarisationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubfamiliarisationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubfamiliarisations::route('/'),
            'create' => CreateSubfamiliarisation::route('/create'),
            'edit' => EditSubfamiliarisation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationLabel(): string
    {
        return 'Subfamiliarisations';
    }
}
