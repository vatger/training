<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('rating_upgrade_pending is disabled until unlocked, then editable and saveable', function () {
    $user = User::factory()->create(['rating_upgrade_pending' => false]);

    $component = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()]);

    expect($component->instance()->isFieldUnlocked('rating_upgrade_pending'))->toBeFalse();

    $component->call('unlockField', 'rating_upgrade_pending');

    expect($component->instance()->isFieldUnlocked('rating_upgrade_pending'))->toBeTrue();

    $component->fillForm(['rating_upgrade_pending' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->rating_upgrade_pending)->toBeTrue();
});

test('last_rating_change and system permission fields stay independently lockable', function () {
    $user = User::factory()->create();

    $component = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()]);

    expect($component->instance()->isFieldUnlocked('rating_change'))->toBeFalse()
        ->and($component->instance()->isFieldUnlocked('system_permissions'))->toBeFalse();

    $component->call('unlockField', 'rating_change');

    expect($component->instance()->isFieldUnlocked('rating_change'))->toBeTrue()
        ->and($component->instance()->isFieldUnlocked('system_permissions'))->toBeFalse();
});
