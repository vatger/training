<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        \Log::info('CreateRole: Form data before create', [
            'has_permission_ids' => isset($data['permission_ids']),
            'permission_ids' => $data['permission_ids'] ?? 'not set',
        ]);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $permissionIds = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);

        \Log::info('CreateRole: Creating role with permissions', [
            'permission_ids' => $permissionIds,
        ]);

        $record = static::getModel()::create($data);

        $record->permissions()->sync($permissionIds);

        \Log::info('CreateRole: Permissions synced', [
            'role_id' => $record->id,
            'synced_ids' => $record->permissions()->pluck('permissions.id')->toArray(),
        ]);

        return $record;
    }
}
