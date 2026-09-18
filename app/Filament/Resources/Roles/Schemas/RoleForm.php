<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permission;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make('Permissions')
                    ->schema([
                        Forms\Components\CheckboxList::make('permission_ids')
                            ->label('Role Permissions')
                            ->helperText('Permissions granted to all users with this role')
                            ->options(function () {
                                return Permission::query()
                                    ->orderBy('group')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($permission) {
                                        $label = $permission->group
                                            ? "[{$permission->group}] {$permission->name}"
                                            : $permission->name;

                                        return [$permission->id => $label];
                                    });
                            })
                            ->columns(2)
                            ->gridDirection('row')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record) {
                                    $component->state($record->permissions->pluck('id')->toArray());
                                }
                            }),
                    ])->columns(1),
            ]);
    }
}
