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
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

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

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_admin || $user->is_superuser) {
            return true;
        }

        return $user->canAccessAdminResource('familiarisations');
    }

    public static function canCreate(): bool
    {
        return static::isAdminOrSuperuser();
    }

    public static function canEdit(Model $record): bool
    {
        return static::isAdminOrSuperuser();
    }

    public static function canDelete(Model $record): bool
    {
        return static::isAdminOrSuperuser();
    }

    private static function isAdminOrSuperuser(): bool
    {
        $user = Filament::auth()->user();

        return (bool) $user && ($user->is_superuser || $user->is_admin);
    }
}
