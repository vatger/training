<?php

use App\Filament\Resources\Examiners\Pages\ListExaminers;
use App\Filament\Support\UserSearch;
use App\Models\Examiner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('UserSearch::callback finds a user by last name, not just first name', function () {
    $user = User::factory()->create(['first_name' => 'Jonathan', 'last_name' => 'Weatherby']);
    User::factory()->create(['first_name' => 'Someone', 'last_name' => 'Else']);

    $results = (UserSearch::callback())('Weatherby');

    expect($results)->toHaveKey($user->id);
});

test('UserSearch::callback finds a user by vatsim id prefix', function () {
    $user = User::factory()->create(['vatsim_id' => 1234567]);
    User::factory()->create(['vatsim_id' => 7654321]);

    $results = (UserSearch::callback())('1234');

    expect($results)->toHaveKey($user->id);
});

test('examiners table user filter still filters correctly after being wired to UserSearch', function () {
    $userA = User::factory()->create(['first_name' => 'Jonathan', 'last_name' => 'Weatherby']);
    $userB = User::factory()->create();

    $examinerA = Examiner::create(['user_id' => $userA->id, 'callsign' => 'ATDGERAAA', 'positions' => ['TWR']]);
    $examinerB = Examiner::create(['user_id' => $userB->id, 'callsign' => 'ATDGERBBB', 'positions' => ['TWR']]);

    Livewire::test(ListExaminers::class)
        ->filterTable('user_id', [$userA->id])
        ->assertCanSeeTableRecords([$examinerA])
        ->assertCanNotSeeTableRecords([$examinerB]);
});
