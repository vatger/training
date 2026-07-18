<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public bool $systemPermissionsUnlocked = false;

    public bool $ratingChangeUnlocked = false;

    public function unlockSystemPermissions(): void
    {
        $this->systemPermissionsUnlocked = true;
    }

    public function unlockRatingChange(): void
    {
        $this->ratingChangeUnlocked = true;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 'permissions' relationship is synced automatically by Filament via CheckboxList::relationship()
        unset($data['permissions']);

        $record->update($data);

        return $record;
    }
}