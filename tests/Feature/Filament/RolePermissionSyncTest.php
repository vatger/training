<?php

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('creating a role syncs the selected permissions via the relationship', function () {
    $permissionA = Permission::create(['name' => 'admin.users.view']);
    $permissionB = Permission::create(['name' => 'admin.users.edit']);

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'Test Role',
            'permissions' => [$permissionA->id, $permissionB->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::where('name', 'Test Role')->first();

    expect($role->permissions->pluck('id')->sort()->values()->all())
        ->toBe([$permissionA->id, $permissionB->id]);
});

test('editing a role re-syncs permissions, including removals', function () {
    $permissionA = Permission::create(['name' => 'admin.users.view']);
    $permissionB = Permission::create(['name' => 'admin.users.edit']);

    $role = Role::create(['name' => 'Test Role']);
    $role->permissions()->sync([$permissionA->id, $permissionB->id]);

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['permissions' => [$permissionA->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->permissions()->pluck('permissions.id')->all())->toBe([$permissionA->id]);
});
