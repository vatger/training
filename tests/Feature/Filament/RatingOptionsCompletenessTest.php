<?php

use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('C2 and I2 (ratings 6 and 9) are valid options on the user rating field', function () {
    $user = User::factory()->create(['rating' => 5]);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['rating' => 6])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->rating)->toBe(6);
});

test('C2 and I2 (ratings 6 and 9) are valid options on course min/max rating fields', function () {
    Livewire::test(CreateCourse::class)
        ->fillForm([
            'name' => 'Test Course',
            'trainee_display_name' => 'Test Course',
            'airport_name' => 'Test Airport',
            'airport_icao' => 'EDDT',
            'type' => 'RTG',
            'position' => 'TWR',
            'min_rating' => 6,
            'max_rating' => 9,
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});
