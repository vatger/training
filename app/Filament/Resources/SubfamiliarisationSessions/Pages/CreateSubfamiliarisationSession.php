<?php

namespace App\Filament\Resources\SubfamiliarisationSessions\Pages;

use App\Filament\Resources\SubfamiliarisationSessions\SubfamiliarisationSessionResource;
use App\Models\Subfamiliarisation;
use App\Models\User;
use App\Services\SubfamiliarisationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSubfamiliarisationSession extends CreateRecord
{
    protected static string $resource = SubfamiliarisationSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $isAdminOrSuperuser = $user->is_admin || $user->is_superuser;
        $subfamiliarisation = Subfamiliarisation::find($data['subfamiliarisation_id']);
        $service = app(SubfamiliarisationService::class);

        if (! $isAdminOrSuperuser) {
            if (! $subfamiliarisation || ! $service->canLogSession($user, $subfamiliarisation)) {
                Notification::make()
                    ->title('You are not a mentor of a course tied to this subfamiliarisation.')
                    ->danger()
                    ->send();

                $this->halt();
            }

            $data['mentor_id'] = $user->id;
            $data['moodle_override'] = false;
        }

        $trainee = User::find($data['user_id']);

        if (
            $subfamiliarisation
            && $trainee
            && ! ($data['moodle_override'] ?? false)
            && ! $service->hasCompletedMoodleRequirements($trainee, $subfamiliarisation)
        ) {
            Notification::make()
                ->title('Moodle courses incomplete')
                ->body('This trainee has not completed all required Moodle courses for this subfamiliarisation yet. An admin or superuser can override this check.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}
