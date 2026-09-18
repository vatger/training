<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Log::info('EditRole: Form data before save', [
            'role_id' => $this->record->id,
            'has_permission_ids' => isset($data['permission_ids']),
            'permission_ids' => $data['permission_ids'] ?? 'not set',
        ]);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $permissionIds = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);

        \Log::info('EditRole: Syncing permissions', [
            'role_id' => $record->id,
            'permission_ids' => $permissionIds,
        ]);

        $record->update($data);

        $record->permissions()->sync($permissionIds);

        \Log::info('EditRole: Permissions after sync', [
            'role_id' => $record->id,
            'synced_ids' => $record->permissions()->pluck('permissions.id')->toArray(),
        ]);

        return $record;
    }
}
