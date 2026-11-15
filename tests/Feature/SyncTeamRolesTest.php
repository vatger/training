<?php

use App\Integrations\Vatger\VatgerClientInterface;
use App\Jobs\SyncTeamRoles;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bindTeams(array $teamsByVatsimId): void
{
    $client = Mockery::mock(VatgerClientInterface::class);
    $client->shouldReceive('getUserTeams')->andReturnUsing(fn (int $id) => $teamsByVatsimId[$id] ?? null);
    app()->instance(VatgerClientInterface::class, $client);
}

test('removes the mentor role and staff flag from a user who left the team', function () {
    $role = Role::firstOrCreate(['name' => 'EDWW Mentor']);
    $user = User::factory()->create(['is_staff' => true]);
    $user->roles()->attach($role->id);

    bindTeams([$user->vatsim_id => []]);

    SyncTeamRoles::dispatchSync();

    expect($user->fresh()->roles)->toBeEmpty()
        ->and($user->fresh()->is_staff)->toBeFalse();
});

test('keeps roles and staff flags when the lookup fails', function () {
    $role = Role::firstOrCreate(['name' => 'EDWW Mentor']);
    $user = User::factory()->create(['is_staff' => true]);
    $user->roles()->attach($role->id);

    bindTeams([]);

    SyncTeamRoles::dispatchSync();

    expect($user->fresh()->roles)->toHaveCount(1)
        ->and($user->fresh()->is_staff)->toBeTrue();
});

test('keeps current mentors and sets leadership flags', function () {
    Role::firstOrCreate(['name' => 'EDGG Mentor']);
    Role::firstOrCreate(['name' => 'ATD Leitung']);
    $user = User::factory()->create(['is_staff' => true]);

    bindTeams([$user->vatsim_id => ['EDGG Mentor', 'ATD Leitung']]);

    SyncTeamRoles::dispatchSync();

    expect($user->fresh()->roles->pluck('name')->all())->toEqualCanonicalizing(['EDGG Mentor', 'ATD Leitung'])
        ->and($user->fresh()->is_staff)->toBeTrue()
        ->and($user->fresh()->is_superuser)->toBeTrue();
});

test('ignores users without roles or staff flags', function () {
    User::factory()->create(['is_staff' => false, 'is_superuser' => false]);

    $client = Mockery::mock(VatgerClientInterface::class);
    $client->shouldNotReceive('getUserTeams');
    app()->instance(VatgerClientInterface::class, $client);

    SyncTeamRoles::dispatchSync();
});

test('can sync a single user', function () {
    $role = Role::firstOrCreate(['name' => 'EDWW Mentor']);
    $target = User::factory()->create(['is_staff' => true]);
    $other = User::factory()->create(['is_staff' => true]);
    $target->roles()->attach($role->id);
    $other->roles()->attach($role->id);

    bindTeams([$target->vatsim_id => [], $other->vatsim_id => []]);

    SyncTeamRoles::dispatchSync($target->vatsim_id);

    expect($target->fresh()->roles)->toBeEmpty()
        ->and($other->fresh()->roles)->toHaveCount(1);
});
