<?php

namespace App\Filament\Resources\FamiliarisationSectors;

use App\Filament\Resources\FamiliarisationSectors\Pages\CreateFamiliarisationSector;
use App\Filament\Resources\FamiliarisationSectors\Pages\EditFamiliarisationSector;
use App\Filament\Resources\FamiliarisationSectors\Pages\ListFamiliarisationSectors;
use App\Filament\Resources\FamiliarisationSectors\Schemas\FamiliarisationSectorForm;
use App\Filament\Resources\FamiliarisationSectors\Tables\FamiliarisationSectorsTable;
use App\Models\FamiliarisationSector;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FamiliarisationSectorResource extends Resource
{
    protected static ?string $model = FamiliarisationSector::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FamiliarisationSectorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FamiliarisationSectorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamiliarisationSectors::route('/'),
            'create' => CreateFamiliarisationSector::route('/create'),
            'edit' => EditFamiliarisationSector::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Endorsements & Ratings';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function getNavigationLabel(): string
    {
        return 'Familiarisation Sectors';
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

        return $user->canAccessAdminResource('familiarisation_sectors');
    }

    public static function canCreate(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_superuser || $user->is_admin) {
            return true;
        }

        return $user->canEditAdminResource('familiarisation_sectors');
    }

    public static function canEdit(Model $record): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_superuser || $user->is_admin) {
            return true;
        }

        return $user->canEditAdminResource('familiarisation_sectors');
    }

    public static function canDelete(Model $record): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return $user->is_superuser || $user->is_admin;
    }
}
