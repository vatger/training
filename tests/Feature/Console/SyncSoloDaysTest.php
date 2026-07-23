<?php

use App\Integrations\VatEud\DTOs\SoloEndorsementData;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function makeSolo(int $id, int $userCid, int $positionDays, string $position = 'EDDL_TWR'): SoloEndorsementData
{
    return SoloEndorsementData::fromApiResponse([
        'id' => $id,
        'user_cid' => $userCid,
        'position' => $position,
        'facility' => 9,
        'instructor_cid' => 1000000,
        'position_days' => $positionDays,
        'expiry' => now()->addDays(30)->toIso8601String(),
        'created_at' => now()->toIso8601String(),
    ]);
}

function bindSoloClient(array $endorsements): void
{
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getSoloEndorsements')->andReturn($endorsements);
    app()->instance(VatEudClientInterface::class, $client);
}

// ─── No solos ─────────────────────────────────────────────────────────────────

test('returns 0 and outputs info when there are no solo endorsements', function () {
    bindSoloClient([]);

    $this->artisan('solo:sync-days')
        ->expectsOutputToContain('No solo endorsements found in VatEUD')
        ->assertExitCode(0);
});

// ─── resetUpgradedUsers ───────────────────────────────────────────────────────

test('resets solo_days_used to 0 for users whose rating increased', function () {
    $user = User::factory()->create([
        'rating' => 4,
        'last_known_rating' => 3,
        'solo_days_used' => 15,
        'rating_upgrade_pending' => true,
    ]);

    bindSoloClient([]);

    $this->artisan('solo:sync-days')->assertExitCode(0);

    $user->refresh();
    expect($user->solo_days_used)->toBe(0);
    expect($user->last_known_rating)->toBe(4);
    expect($user->rating_upgrade_pending)->toBeFalse();
});

test('resets multiple upgraded users before syncing', function () {
    $u1 = User::factory()->create(['rating' => 5, 'last_known_rating' => 3, 'solo_days_used' => 10]);
    $u2 = User::factory()->create(['rating' => 4, 'last_known_rating' => 3, 'solo_days_used' => 20]);

    bindSoloClient([]);

    $this->artisan('solo:sync-days')->assertExitCode(0);

    $u1->refresh();
    $u2->refresh();
    expect($u1->solo_days_used)->toBe(0);
    expect($u2->solo_days_used)->toBe(0);
});

test('does NOT reset solo_days_used when rating has not changed', function () {
    $user = User::factory()->create([
        'rating' => 3,
        'last_known_rating' => 3,
        'solo_days_used' => 12,
    ]);

    bindSoloClient([]);

    $this->artisan('solo:sync-days')->assertExitCode(0);

    $user->refresh();
    expect($user->solo_days_used)->toBe(12);
});

test('does NOT reset user whose rating is lower than last_known_rating', function () {
    // Edge case: rating downgrade should not trigger a reset
    $user = User::factory()->create([
        'rating' => 2,
        'last_known_rating' => 3,
        'solo_days_used' => 8,
    ]);

    bindSoloClient([]);

    $this->artisan('solo:sync-days')->assertExitCode(0);

    $user->refresh();
    expect($user->solo_days_used)->toBe(8);
});

// ─── solo day sync: updates user ─────────────────────────────────────────────

test('updates solo_days_used when new days exceed current', function () {
    $user = User::factory()->create([
        'vatsim_id' => 1234567,
        'solo_days_used' => 5,
        'rating' => 3,
        'last_known_rating' => 3,
    ]);

    bindSoloClient([makeSolo(1, 1234567, 20)]);

    $this->artisan('solo:sync-days')->assertExitCode(0);

    $user->refresh();
    expect($user->solo_days_used)->toBe(20);
});

test('does NOT update solo_days_used when new days equal current', function () {
    $user = User::factory()->create([
        'vatsim_id' => 1234567,
        'solo_days_used' => 10,
        'rating' => 3,
        'last_known_rating' => 3,
    ]);

    bindSoloClient([makeSolo(1, 1234567, 10)]);

    $this->artisan('solo:sync-days')->assertExitCode(0);

    $user->refresh();
    expect($user->solo_days_used)->toBe(10);
});

test('does NOT update solo_days_used when new days are less than current', function () {
    $user = User::factory()->create([
        'vatsim_id' => 1234567,
        'solo_days_used' => 25,
        'rating' => 3,
        'last_known_rating' => 3,
    ]);

    bindSoloClient([makeSolo(1, 1234567, 5)]);

    $this->artisan('solo:sync-days')->assertExitCode(0);

    $user->refresh();
    expect($user->solo_days_used)->toBe(25);
});

test('uses the maximum position_days across multiple solos for the same user', function () {
    $user = User::factory()->create([
        'vatsim_id' => 1234567,
        'solo_days_used' => 0,
        'rating' => 3,
        'last_known_rating' => 3,
    ]);

    bindSoloClient([
        makeSolo(1, 1234567, 8, 'EDDL_TWR'),
        makeSolo(2, 1234567, 20, 'EDDF_APP'),
        makeSolo(3, 1234567, 3, 'EDDH_GND'),
    ]);

    $this->artisan('solo:sync-days')->assertExitCode(0);

    $user->refresh();
    expect($user->solo_days_used)->toBe(20);
});

test('updates each user independently when multiple users have solos', function () {
    $u1 = User::factory()->create(['vatsim_id' => 1111111, 'solo_days_used' => 0, 'rating' => 3, 'last_known_rating' => 3]);
    $u2 = User::factory()->create(['vatsim_id' => 2222222, 'solo_days_used' => 0, 'rating' => 3, 'last_known_rating' => 3]);

    bindSoloClient([
        makeSolo(1, 1111111, 12),
        makeSolo(2, 2222222, 28),
    ]);

    $this->artisan('solo:sync-days')->assertExitCode(0);

    $u1->refresh();
    $u2->refresh();
    expect($u1->solo_days_used)->toBe(12);
    expect($u2->solo_days_used)->toBe(28);
});

// ─── Unknown user ────────────────────────────────────────────────────────────

test('outputs a warning when solo endorsement references an unknown VATSIM ID', function () {
    // No user with vatsim_id 9999999 exists
    bindSoloClient([makeSolo(1, 9999999, 10)]);

    $this->artisan('solo:sync-days')
        ->expectsOutputToContain('not found')
        ->assertExitCode(0);
});

// ─── Error handling ───────────────────────────────────────────────────────────

test('returns exit code 1 when client throws an exception', function () {
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getSoloEndorsements')->andThrow(new RuntimeException('API down'));
    app()->instance(VatEudClientInterface::class, $client);

    $this->artisan('solo:sync-days')
        ->expectsOutputToContain('Error during solo days sync')
        ->assertExitCode(1);
});
