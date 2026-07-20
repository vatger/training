<?php

namespace App\Filament\Resources\LeadingMentors;

use App\Filament\Resources\LeadingMentors\Pages\CreateLeadingMentor;
use App\Filament\Resources\LeadingMentors\Pages\EditLeadingMentor;
use App\Filament\Resources\LeadingMentors\Pages\ListLeadingMentors;
use App\Filament\Resources\LeadingMentors\Schemas\LeadingMentorForm;
use App\Filament\Resources\LeadingMentors\Tables\LeadingMentorsTable;
use App\Models\LeadingMentor;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeadingMentorResource extends Resource
{
    protected static ?string $model = LeadingMentor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Leading Mentors';

    public static function form(Schema $schema): Schema
    {
        return LeadingMentorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadingMentorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Permissions';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeadingMentors::route('/'),
            'create' => CreateLeadingMentor::route('/create'),
            'edit' => EditLeadingMentor::route('/{record}/edit'),
        ];
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

        return $user->canAccessAdminResource('leading_mentors');
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

        return $user->canEditAdminResource('leading_mentors');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_superuser || $user->is_admin) {
            return true;
        }

        return $user->canEditAdminResource('leading_mentors');
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return $user->is_superuser || $user->is_admin;
    }
}
