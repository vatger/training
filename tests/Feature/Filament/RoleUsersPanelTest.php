<?php

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('role edit page shows a read-only panel of users with that role', function () {
    $role = Role::create(['name' => 'EDGG Mentor']);
    $member = User::factory()->create(['first_name' => 'Some', 'last_name' => 'Mentor', 'vatsim_id' => 7654321]);
    $role->users()->attach($member->id);

    $response = $this->get(RoleResource::getUrl('edit', ['record' => $role]));

    $response->assertSuccessful();
    $response->assertSee('Some Mentor');
    $response->assertSee('7654321');
});

test('role create page renders without error when there is no record yet', function () {
    $this->get(RoleResource::getUrl('create'))->assertSuccessful();
});
