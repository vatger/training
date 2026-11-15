<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog;
use App\Filament\Resources\ActivityLogs\Tables\ActivityLogsTable;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return ActivityLogsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        // Admin-panel change log entries (is_admin_action) are only ever
        // visible to admins — a superuser (or anyone else who can open this
        // resource) still sees every other, pre-existing log entry.
        return parent::getEloquentQuery()->visibleTo(Filament::auth()->user());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System & Administration';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }
}
