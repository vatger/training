<?php

namespace App\Filament\Resources\CptLogs;

use App\Filament\Resources\CptLogs\Pages\ListCptLogs;
use App\Filament\Resources\CptLogs\Pages\ViewCptLog;
use App\Filament\Resources\CptLogs\Tables\CptLogsTable;
use App\Models\CptLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class CptLogResource extends Resource
{
    protected static ?string $model = CptLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return CptLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCptLogs::route('/'),
            'view' => ViewCptLog::route('/{record}'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Training';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function getNavigationLabel(): string
    {
        return 'CPT Logs';
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

        return $user->canAccessAdminResource('cpt_logs');
    }

    public static function canCreate(): bool
    {
        return false;
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

        return $user->canEditAdminResource('cpt_logs');
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