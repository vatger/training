<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\ActivityLog;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    /** @var array<int, string> */
    protected array $originalPermissionNames = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->originalPermissionNames = $this->record->permissions()->pluck('name')->sort()->values()->all();
    }

    protected function afterSave(): void
    {
        $newPermissionNames = $this->record->permissions()->pluck('name')->sort()->values()->all();

        if ($newPermissionNames === $this->originalPermissionNames) {
            return;
        }

        ActivityLog::record(
            'role.permissions_updated',
            (auth()->user()?->name ?? 'System')." updated permissions for role \"{$this->record->name}\"",
            $this->record,
            [
                'added' => array_values(array_diff($newPermissionNames, $this->originalPermissionNames)),
                'removed' => array_values(array_diff($this->originalPermissionNames, $newPermissionNames)),
            ],
        );
    }
}
