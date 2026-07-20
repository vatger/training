<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Log::info('EditUser: Form data before save', [
            'user_id' => $this->record->id,
            'has_permission_ids' => isset($data['permission_ids']),
            'permission_ids' => $data['permission_ids'] ?? 'not set',
        ]);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $permissionIds = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);

        \Log::info('EditUser: Syncing permissions', [
            'user_id' => $record->id,
            'permission_ids' => $permissionIds,
            'is_array' => is_array($permissionIds),
            'count' => is_array($permissionIds) ? count($permissionIds) : 0,
        ]);

        $record->update($data);

        $record->permissions()->sync($permissionIds);

        $afterSync = $record->permissions()->pluck('permissions.id')->toArray();
        \Log::info('EditUser: Permissions after sync', [
            'user_id' => $record->id,
            'synced_ids' => $afterSync,
        ]);

        return $record;
    }

    protected function afterSave(): void
    {
        \Log::info('EditUser: After save completed', [
            'user_id' => $this->record->id,
            'current_permissions' => $this->record->permissions->pluck('name')->toArray(),
        ]);
    }
}
