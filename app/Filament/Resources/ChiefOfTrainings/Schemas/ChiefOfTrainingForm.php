<?php

namespace App\Filament\Resources\ChiefOfTrainings\Schemas;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ChiefOfTrainingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' ('.$record->vatsim_id.')')
                    ->searchable(['first_name', 'last_name', 'vatsim_id'])
                    ->required()
                    ->helperText('Select the user who will be Chief of Training for this course'),

                Forms\Components\Select::make('course_id')
                    ->label('Course')
                    ->relationship(
                        name: 'course',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
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

                                return $query->whereHas('mentorGroup', function ($q) use ($lmFirs) {
                                    $q->where(function ($q2) use ($lmFirs) {
                                        foreach ($lmFirs as $fir) {
                                            $q2->orWhere('name', 'LIKE', "%{$fir}%");
                                        }
                                    });
                                });
                            }

                            return $query;
                        }
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn ($record) => $record->name.' ('.$record->type.' - '.$record->position.')'
                    )
                    ->searchable()
                    ->required()
                    ->helperText('Select the course this user will manage'),
            ]);
    }
}
