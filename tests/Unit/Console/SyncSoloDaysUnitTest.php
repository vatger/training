<?php

/**
 * Unit tests for SyncSoloDays — methods tested via reflection.
 * Complements Feature/Console/SyncSoloDaysTest.php (artisan-level).
 * Focus: resetUpgradedUsers() called directly with precise DB assertions,
 * and the per-user solo-day update logic.
 */

use App\Console\Commands\SyncSoloDays;
use App\Integrations\VatEud\DTOs\SoloEndorsementData;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\User;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function soloDaysMakeCommand(VatEudClientInterface $client): SyncSoloDays
{
    return new SyncSoloDays($client);
}

function soloDaysSetIO(object $command): BufferedOutput
{
    $buffered = new BufferedOutput;
    $prop = new ReflectionProperty($command, 'output');
    $prop->setAccessible(true);
    $prop->setValue($command, new OutputStyle(new ArrayInput([]), $buffered));

    return $buffered;
}

function soloDaysCall(object $cmd, string $method, mixed ...$args): mixed
{
    $m = new ReflectionMethod($cmd, $method);
    $m->setAccessible(true);

    return $m->invoke($cmd, ...$args);
}

function soloDaysMakeSolo(int $id, int $cid, int $days, string $pos = 'EDDL_TWR'): SoloEndorsementData
{
    return SoloEndorsementData::fromApiResponse([
        'id' => $id, 'user_cid' => $cid, 'position' => $pos, 'facility' => 9,
        'instructor_cid' => 1000000, 'position_days' => $days,
        'expiry' => now()->addDays(30)->toIso8601String(),
        'created_at' => now()->toIso8601String(),
    ]);
}

// ─── resetUpgradedUsers: direct DB reset ──────────────────────────────────────

test('resetUpgradedUsers resets solo_days_used to 0 for upgraded user', function () {
    $user = User::factory()->create(['rating' => 4, 'last_known_rating' => 3, 'solo_days_used' => 20]);

    $cmd = soloDaysMakeCommand(Mockery::mock(VatEudClientInterface::class));
    soloDaysSetIO($cmd);
    soloDaysCall($cmd, 'resetUpgradedUsers');

    $user->refresh();
    expect($user->solo_days_used)->toBe(0);
});

test('resetUpgradedUsers updates last_known_rating to current rating', function () {
    $user = User::factory()->create(['rating' => 5, 'last_known_rating' => 3, 'solo_days_used' => 10]);

    $cmd = soloDaysMakeCommand(Mockery::mock(VatEudClientInterface::class));
    soloDaysSetIO($cmd);
    soloDaysCall($cmd, 'resetUpgradedUsers');

    $user->refresh();
    expect($user->last_known_rating)->toBe(5);
});

test('resetUpgradedUsers clears rating_upgraded_at', function () {
    $user = User::factory()->create([
        'rating' => 4, 'last_known_rating' => 2,
        'solo_days_used' => 8, 'rating_upgraded_at' => now()->subDays(5),
    ]);

    $cmd = soloDaysMakeCommand(Mockery::mock(VatEudClientInterface::class));
    soloDaysSetIO($cmd);
    soloDaysCall($cmd, 'resetUpgradedUsers');

    $user->refresh();
    expect($user->rating_upgraded_at)->toBeNull();
});

test('resetUpgradedUsers resets all upgraded users in one call', function () {
    $u1 = User::factory()->create(['rating' => 4, 'last_known_rating' => 3, 'solo_days_used' => 15]);
    $u2 = User::factory()->create(['rating' => 5, 'last_known_rating' => 3, 'solo_days_used' => 25]);
    $u3 = User::factory()->create(['rating' => 3, 'last_known_rating' => 3, 'solo_days_used' => 10]); // not upgraded

    $cmd = soloDaysMakeCommand(Mockery::mock(VatEudClientInterface::class));
    soloDaysSetIO($cmd);
    soloDaysCall($cmd, 'resetUpgradedUsers');

    $u1->refresh();
    $u2->refresh();
    $u3->refresh();

    expect($u1->solo_days_used)->toBe(0);
    expect($u2->solo_days_used)->toBe(0);
    expect($u3->solo_days_used)->toBe(10); // unchanged
});

test('resetUpgradedUsers does nothing when no users have upgraded', function () {
    User::factory()->create(['rating' => 3, 'last_known_rating' => 3, 'solo_days_used' => 12]);
    User::factory()->create(['rating' => 3, 'last_known_rating' => 3, 'solo_days_used' => 7]);

    $cmd = soloDaysMakeCommand(Mockery::mock(VatEudClientInterface::class));
    soloDaysSetIO($cmd);
    soloDaysCall($cmd, 'resetUpgradedUsers');

    expect(User::where('solo_days_used', 0)->count())->toBe(0);
});

test('resetUpgradedUsers is a no-op when no users exist', function () {
    $cmd = soloDaysMakeCommand(Mockery::mock(VatEudClientInterface::class));
    soloDaysSetIO($cmd);
    soloDaysCall($cmd, 'resetUpgradedUsers');
    expect(true)->toBeTrue(); // just confirm no exception
});

test('resetUpgradedUsers does NOT reset user whose rating is lower than last_known_rating', function () {
    // Downgrade scenario — should not be reset
    $user = User::factory()->create(['rating' => 2, 'last_known_rating' => 3, 'solo_days_used' => 8]);

    $cmd = soloDaysMakeCommand(Mockery::mock(VatEudClientInterface::class));
    soloDaysSetIO($cmd);
    soloDaysCall($cmd, 'resetUpgradedUsers');

    $user->refresh();
    expect($user->solo_days_used)->toBe(8);
});

test('resetUpgradedUsers handles large rating jump (e.g. S1 → C1)', function () {
    $user = User::factory()->create(['rating' => 5, 'last_known_rating' => 1, 'solo_days_used' => 30]);

    $cmd = soloDaysMakeCommand(Mockery::mock(VatEudClientInterface::class));
    soloDaysSetIO($cmd);
    soloDaysCall($cmd, 'resetUpgradedUsers');

    $user->refresh();
    expect($user->solo_days_used)->toBe(0);
    expect($user->last_known_rating)->toBe(5);
});

// ─── handle: solo day update logic (via full command run with mock) ────────────

// Manually-constructed commands need setLaravel() so Command::run() can
// resolve OutputStyle and other components via the container.
function soloDaysRun(SyncSoloDays $cmd): void
{
    $cmd->setLaravel(app());
    $cmd->run(
        new ArrayInput([]),
        new NullOutput,
    );
}

test('handle updates user when VatEUD reports more solo days than stored', function () {
    $user = User::factory()->create(['vatsim_id' => 1234567, 'solo_days_used' => 3, 'rating' => 3, 'last_known_rating' => 3]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getSoloEndorsements')->andReturn([soloDaysMakeSolo(1, 1234567, 18)]);

    soloDaysRun(soloDaysMakeCommand($client));

    $user->refresh();
    expect($user->solo_days_used)->toBe(18);
});

test('handle does not update user when VatEUD reports the same solo days', function () {
    $user = User::factory()->create(['vatsim_id' => 1234567, 'solo_days_used' => 10, 'rating' => 3, 'last_known_rating' => 3]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getSoloEndorsements')->andReturn([soloDaysMakeSolo(1, 1234567, 10)]);

    soloDaysRun(soloDaysMakeCommand($client));

    $user->refresh();
    expect($user->solo_days_used)->toBe(10);
});

test('handle takes maximum across multiple solos for the same user', function () {
    $user = User::factory()->create(['vatsim_id' => 1234567, 'solo_days_used' => 0, 'rating' => 3, 'last_known_rating' => 3]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getSoloEndorsements')->andReturn([
        soloDaysMakeSolo(1, 1234567, 5, 'EDDL_TWR'),
        soloDaysMakeSolo(2, 1234567, 22, 'EDDF_APP'),
        soloDaysMakeSolo(3, 1234567, 9, 'EDDH_GND'),
    ]);

    soloDaysRun(soloDaysMakeCommand($client));

    $user->refresh();
    expect($user->solo_days_used)->toBe(22);
});

test('handle resets upgraded user before processing solos', function () {
    $user = User::factory()->create([
        'vatsim_id' => 1234567,
        'rating' => 4,
        'last_known_rating' => 3,
        'solo_days_used' => 20,
    ]);

    $client = Mockery::mock(VatEudClientInterface::class);
    // VatEUD reports 5 days — less than current 20, but reset happened first (0→5)
    $client->shouldReceive('getSoloEndorsements')->andReturn([soloDaysMakeSolo(1, 1234567, 5)]);

    soloDaysRun(soloDaysMakeCommand($client));

    $user->refresh();
    expect($user->solo_days_used)->toBe(5);
    expect($user->last_known_rating)->toBe(4);
});
