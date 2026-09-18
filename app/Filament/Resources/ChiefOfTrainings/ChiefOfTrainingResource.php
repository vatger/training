<?php

namespace App\Filament\Resources\ChiefOfTrainings;

use App\Filament\Resources\ChiefOfTrainings\Pages\CreateChiefOfTraining;
use App\Filament\Resources\ChiefOfTrainings\Pages\EditChiefOfTraining;
use App\Filament\Resources\ChiefOfTrainings\Pages\ListChiefOfTrainings;
use App\Filament\Resources\ChiefOfTrainings\Schemas\ChiefOfTrainingForm;
use App\Filament\Resources\ChiefOfTrainings\Tables\ChiefOfTrainingsTable;
use App\Models\ChiefOfTraining;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChiefOfTrainingResource extends Resource
{
    protected static ?string $model = ChiefOfTraining::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Chiefs of Training';

    public static function form(Schema $schema): Schema
    {
        return ChiefOfTrainingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChiefOfTrainingsTable::configure($table);
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
            'index' => ListChiefOfTrainings::route('/'),
            'create' => CreateChiefOfTraining::route('/create'),
            'edit' => EditChiefOfTraining::route('/{record}/edit'),
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

        if ($user->isLeadingMentor()) {
            return true;
        }

        return $user->canAccessAdminResource('chief_of_trainings');
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

        if ($user->isLeadingMentor()) {
            return true;
        }

        return $user->canEditAdminResource('chief_of_trainings');
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

        if ($user->isLeadingMentor() && $record->course) {
            $course = $record->course;
            if ($course->mentor_group_id) {
                $mentorGroupName = $course->mentorGroup?->name;
                if ($mentorGroupName) {
                    $fir = $user->getFirFromMentorGroup($mentorGroupName);
                    if ($fir && $user->isLeadingMentorForFir($fir)) {
                        return true;
                    }
                }
            }

            return false;
        }

        return $user->canEditAdminResource('chief_of_trainings');
    }

    public static function canDelete(Model $record): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_superuser || $user->is_admin) {
            return true;
        }

        if ($user->isLeadingMentor() && $record->course) {
            $course = $record->course;
            if ($course->mentor_group_id) {
                $mentorGroupName = $course->mentorGroup?->name;
                if ($mentorGroupName) {
                    $fir = $user->getFirFromMentorGroup($mentorGroupName);
                    if ($fir && $user->isLeadingMentorForFir($fir)) {
                        return true;
                    }
                }
            }

            return false;
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['course.mentorGroup']);
        $user = Filament::auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->is_superuser || $user->is_admin) {
            return $query;
        }

        if ($user->isLeadingMentor()) {
            $lmFirs = $user->getLeadingMentorFirs();

            if (empty($lmFirs)) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas('course.mentorGroup', function ($q) use ($lmFirs) {
                $q->where(function ($q2) use ($lmFirs) {
                    foreach ($lmFirs as $fir) {
                        $q2->orWhere('name', 'LIKE', "%{$fir}%");
                    }
                });
            });
        }

        if ($user->canAccessAdminResource('chief_of_trainings')) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }
}
