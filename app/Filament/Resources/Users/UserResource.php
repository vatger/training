<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $recordTitleAttribute = 'vatsim_id';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        // Keeps the "GDPR Removed" placeholder out of lists and searches.
        return parent::getEloquentQuery()->excludeGdprDeleted();
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'vatsim_id'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * Users are routed by VATSIM ID, which GDPR-anonymised users no longer have,
     * so there is no edit page to link to for them.
     */
    public static function getEditUrl(?User $user): ?string
    {
        return filled($user?->vatsim_id) ? static::getUrl('edit', ['record' => $user]) : null;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Users & Access';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }
}
