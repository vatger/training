<?php

use App\Enums\ActivityAction;
use App\Filament\Resources\ApiKeys\ApiKeyResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('api keys are hidden from superusers', function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));

    expect(ApiKeyResource::canViewAny())->toBeFalse();
    $this->get(ApiKeyResource::getUrl('index'))->assertForbidden();
});

test('api keys are available to admins', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    expect(ApiKeyResource::canViewAny())->toBeTrue();
    $this->get(ApiKeyResource::getUrl('index'))->assertSuccessful();
});

test('superusers do not see the admin account toggle', function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
    $target = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->assertFormFieldIsHidden('is_admin');
});

test('admins can make another user an admin', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $target = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->assertFormFieldIsVisible('is_admin')
        ->call('unlockField', 'system_permissions')
        ->fillForm(['is_admin' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->refresh()->is_admin)->toBeTrue();
});

test('a superuser cannot grant admin even by sending the field anyway', function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
    $target = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->call('unlockField', 'system_permissions')
        ->set('data.is_admin', true)
        ->call('save');

    expect($target->refresh()->is_admin)->toBeFalse();
});

test('the default admin cid is always an admin', function () {
    $user = User::factory()->create(['vatsim_id' => User::DEFAULT_ADMIN_CID, 'is_admin' => false]);

    expect($user->refresh()->is_admin)->toBeTrue();

    $user->update(['is_admin' => false]);

    expect($user->refresh()->is_admin)->toBeTrue();
});

test('other users are not made admin automatically', function () {
    expect(User::factory()->create()->is_admin)->toBeFalse();
});

test('team overview template changes have a label and colour in the activity log', function () {
    $action = ActivityAction::fromString('teamoverviewtemplate.updated');

    expect($action)->not->toBeNull()
        ->and($action->getLabel())->toBe('ATD Team Overview Template Updated')
        ->and($action->getColor())->toBe('warning');
});

test('the profile only marks users holding the ATD Leitung role as ATD leadership', function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));

    $atdLeader = User::factory()->create(['is_superuser' => true]);
    $atdLeader->roles()->attach(Role::create(['name' => 'ATD Leitung']));
    $vatgerLeader = User::factory()->create(['is_superuser' => true]);
    $vatgerLeader->roles()->attach(Role::create(['name' => 'VATGER Leitung']));
    $plainSuperuser = User::factory()->create(['is_superuser' => true]);

    $this->get(route('users.profile', $atdLeader->vatsim_id))
        ->assertInertia(fn ($page) => $page->where('userData.user.is_atd_leadership', true));

    foreach ([$vatgerLeader, $plainSuperuser] as $user) {
        $this->get(route('users.profile', $user->vatsim_id))
            ->assertInertia(fn ($page) => $page->where('userData.user.is_atd_leadership', false)->where('userData.user.is_superuser', true));
    }
});

test('subfamiliarisation session changes have a label and colour in the activity log', function () {
    $action = ActivityAction::fromString('subfamiliarisationsession.created');

    expect($action)->not->toBeNull()
        ->and($action->getLabel())->toBe('Subfamiliarisation Session Created')
        ->and($action->getColor())->toBe('success');
});
