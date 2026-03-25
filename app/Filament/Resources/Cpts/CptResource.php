<?php

namespace App\Filament\Resources\Cpts;

use App\Filament\Resources\Cpts\Pages\ListCpts;
use App\Filament\Resources\Cpts\Pages\ViewCpt;
use App\Filament\Resources\Cpts\Pages\EditCpt;
use App\Filament\Resources\Cpts\Tables\CptsTable;
use App\Models\Cpt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class CptResource extends Resource
{
    protected static ?string $model = Cpt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return CptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCpts::route('/'),
            'view' => ViewCpt::route('/{record}'),
            'edit' => EditCpt::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Training';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function getNavigationLabel(): string
    {
        return 'CPTs';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return false;
        }

        if ($user->is_admin || $user->is_superuser) {
            return true;
        }

        return $user->canAccessAdminResource('cpts');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return false;
        }

        if ($user->is_superuser || $user->is_admin) {
            return true;
        }

        return $user->canEditAdminResource('cpts');
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return false;
        }

        return $user->is_superuser || $user->is_admin;
    }
}