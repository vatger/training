<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function delegatedUserEditor(): User
{
    $editor = User::factory()->create(['is_superuser' => false, 'is_admin' => false]);
    $editor->permissions()->attach([
        Permission::create(['name' => 'admin.access'])->id,
        Permission::create(['name' => 'admin.users.view'])->id,
        Permission::create(['name' => 'admin.users.edit'])->id,
    ]);

    return $editor;
}

test('a user with only admin.users.edit cannot grant superuser or staff', function () {
    $editor = delegatedUserEditor();
    $this->actingAs($editor);

    Livewire::test(EditUser::class, ['record' => $editor->getRouteKey()])
        ->call('unlockField', 'system_permissions')
        ->fillForm(['is_superuser' => true, 'is_staff' => true])
        ->call('save');

    $editor->refresh();

    expect($editor->is_superuser)->toBeFalse()
        ->and($editor->is_staff)->toBeFalse();
});

test('a user with only admin.users.edit cannot grant themselves roles or permissions', function () {
    $editor = delegatedUserEditor();
    $role = Role::create(['name' => 'VATGER Leitung']);
    $extra = Permission::create(['name' => 'admin.roles.edit']);
    $this->actingAs($editor);

    Livewire::test(EditUser::class, ['record' => $editor->getRouteKey()])
        ->fillForm(['roles' => [$role->id], 'permissions' => [$extra->id]])
        ->call('save');

    $editor->refresh();

    expect($editor->roles()->count())->toBe(0)
        ->and($editor->permissions()->where('name', 'admin.roles.edit')->exists())->toBeFalse();
});

test('a superuser can still change roles and the staff flag', function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
    $target = User::factory()->create();
    $role = Role::create(['name' => 'EDGG Mentor']);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->call('unlockField', 'system_permissions')
        ->fillForm(['roles' => [$role->id], 'is_staff' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->refresh()->is_staff)->toBeTrue()
        ->and($target->roles()->pluck('name')->all())->toBe(['EDGG Mentor']);
});

test('only an admin can change the admin flag', function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true, 'is_admin' => false]));
    $target = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->call('unlockField', 'system_permissions')
        ->fillForm(['is_admin' => true])
        ->call('save');

    expect($target->refresh()->is_admin)->toBeFalse();
});
