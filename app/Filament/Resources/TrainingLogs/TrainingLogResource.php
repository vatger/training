<?php

namespace App\Filament\Resources\TrainingLogs;

use App\Filament\Resources\TrainingLogs\Pages\CreateTrainingLog;
use App\Filament\Resources\TrainingLogs\Pages\EditTrainingLog;
use App\Filament\Resources\TrainingLogs\Pages\ListTrainingLogs;
use App\Filament\Resources\TrainingLogs\Pages\ViewTrainingLog;
use App\Filament\Resources\TrainingLogs\Schemas\TrainingLogForm;
use App\Filament\Resources\TrainingLogs\Tables\TrainingLogsTable;
use App\Models\TrainingLog;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TrainingLogResource extends Resource
{
    protected static ?string $model = TrainingLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return TrainingLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrainingLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainingLogs::route('/'),
            'create' => CreateTrainingLog::route('/create'),
            'view' => ViewTrainingLog::route('/{record}'),
            'edit' => EditTrainingLog::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Training';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationLabel(): string
    {
        return 'Training Logs';
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

        return $user->canAccessAdminResource('training_logs');
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

        return $user->canEditAdminResource('training_logs');
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

        return $user->canEditAdminResource('training_logs');
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
