<?php

namespace App\Filament\Resources\Permissions;

use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Models\Permission;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('description')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('group')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('users_count')
                    ->label('Direct Users')
                    ->counts('users')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('roles_count')
                    ->label('Roles')
                    ->counts('roles')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
            ])
            ->defaultSort('group', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return $user->is_admin || $user->is_superuser;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Permissions';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getNavigationLabel(): string
    {
        return 'Permissions';
    }
}
