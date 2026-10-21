<?php

namespace App\Filament\Resources\SubfamiliarisationSessions\Pages;

use App\Filament\Resources\SubfamiliarisationSessions\SubfamiliarisationSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubfamiliarisationSession extends EditRecord
{
    protected static string $resource = SubfamiliarisationSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if (! ($user->is_admin || $user->is_superuser)) {
            $data['mentor_id'] = $this->record->mentor_id;
            $data['moodle_override'] = $this->record->moodle_override;
        }

        return $data;
    }
}
