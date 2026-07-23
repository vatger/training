<?php

namespace App\Filament\Resources\EndorsementActivities;

use App\Filament\Resources\EndorsementActivities\Pages\EditEndorsementActivity;
use App\Filament\Resources\EndorsementActivities\Pages\ListEndorsementActivities;
use App\Filament\Resources\EndorsementActivities\Pages\ViewEndorsementActivity;
use App\Filament\Resources\EndorsementActivities\Schemas\EndorsementActivityForm;
use App\Filament\Resources\EndorsementActivities\Tables\EndorsementActivitiesTable;
use App\Models\EndorsementActivity;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EndorsementActivityResource extends Resource
{
    protected static ?string $model = EndorsementActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?string $recordTitleAttribute = 'position';

    public static function form(Schema $schema): Schema
    {
        return EndorsementActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EndorsementActivitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEndorsementActivities::route('/'),
            'view' => ViewEndorsementActivity::route('/{record}'),
            'edit' => EditEndorsementActivity::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Endorsements & Ratings';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationLabel(): string
    {
        return 'Endorsement Activities';
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

        return $user->canAccessAdminResource('endorsement_activities');
    }

    public static function canCreate(): bool
    {
        return false;
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

        return $user->canEditAdminResource('endorsement_activities');
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
