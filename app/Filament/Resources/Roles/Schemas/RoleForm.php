<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permission;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
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
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Role Permissions')
                            ->helperText('Permissions granted to all users with this role')
                            ->relationship('permissions', 'id')
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
                            ->gridDirection('row'),
                    ])->columns(1),

                Section::make('Users With This Role')
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make('users')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record?->users ?? [])
                            ->table([
                                TableColumn::make('Name'),
                                TableColumn::make('VATSIM ID'),
                            ])
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('vatsim_id'),
                            ])
                            ->placeholder('No users have this role. Assigned via VATSIM team sync, or manually on a user\'s Roles & Permissions section.'),
                    ])->columns(1),
            ]);
    }
}
