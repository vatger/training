<?php

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\WaitingListRestrictions\WaitingListRestrictionResource;
use App\Models\User;
use App\Models\WaitingListRestriction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('users can be found by global search on first or last name, not just vatsim id', function () {
    $user = User::factory()->create(['first_name' => 'Jonathan', 'last_name' => 'Weatherby']);
    User::factory()->create(['first_name' => 'Someone', 'last_name' => 'Else']);

    $results = UserResource::getGlobalSearchResults('Weatherby');

    expect($results->pluck('title')->implode(', '))->toContain((string) $user->vatsim_id);
});

test('waiting list restrictions can be found by global search on the restricted user\'s name', function () {
    $user = User::factory()->create(['first_name' => 'Jonathan', 'last_name' => 'Weatherby']);
    $restriction = WaitingListRestriction::create(['user_id' => $user->id, 'type' => 'RTG']);

    $results = WaitingListRestrictionResource::getGlobalSearchResults('Weatherby');

    expect($results->pluck('url'))->toContain(WaitingListRestrictionResource::getUrl('edit', ['record' => $restriction]));
});
