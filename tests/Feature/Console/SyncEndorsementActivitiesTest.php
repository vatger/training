<?php

use App\Integrations\VatEud\DTOs\Tier1EndorsementData;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\EndorsementActivity;
use App\Services\VatsimActivityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function makeTier1(int $id, int $userCid, string $position, string $createdAt = '2025-01-01T00:00:00Z'): Tier1EndorsementData
{
    return Tier1EndorsementData::fromApiResponse([
        'id' => $id,
        'user_cid' => $userCid,
        'position' => $position,
        'facility' => 9,
        'created_at' => $createdAt,
    ]);
}

function mockActivity(float $minutes = 0.0, ?Carbon $lastDate = null): array
{
    return ['minutes' => $minutes, 'last_activity_date' => $lastDate];
}

function bindActivityService(float $minutes = 0.0, ?Carbon $lastDate = null): void
{
    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')->andReturn(mockActivity($minutes, $lastDate));
    $svc->shouldReceive('calculateEligibleSince')->andReturn(null);
    app()->instance(VatsimActivityService::class, $svc);
}

// ─── syncAllTier1Endorsements: new records ─────────────────────────────────────

test('creates an EndorsementActivity record for a new endorsement', function () {
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1(42, 1234567, 'EDDL_TWR')]);
    app()->instance(VatEudClientInterface::class, $client);
    bindActivityService();

    $this->artisan('endorsements:sync-activities')->assertExitCode(0);

    $this->assertDatabaseHas('endorsement_activities', [
        'endorsement_id' => 42,
        'vatsim_id' => 1234567,
        'position' => 'EDDL_TWR',
    ]);
});

test('creates records for multiple new endorsements', function () {
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([
        makeTier1(1, 1000001, 'EDDL_TWR'),
        makeTier1(2, 1000002, 'EDDF_APP'),
        makeTier1(3, 1000003, 'EDWW_N_CTR'),
    ]);
    app()->instance(VatEudClientInterface::class, $client);
    bindActivityService();

    $this->artisan('endorsements:sync-activities')->assertExitCode(0);

    expect(EndorsementActivity::count())->toBe(3);
});

// ─── syncAllTier1Endorsements: skip existing ───────────────────────────────────

test('skips creating a record when endorsement_id already exists in DB', function () {
    EndorsementActivity::create([
        'endorsement_id' => 42,
        'vatsim_id' => 1234567,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0.0,
        'created_at_vateud' => now(),
        'last_updated' => now(),
    ]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1(42, 1234567, 'EDDL_TWR')]);
    app()->instance(VatEudClientInterface::class, $client);
    bindActivityService();

    $this->artisan('endorsements:sync-activities')->assertExitCode(0);

    expect(EndorsementActivity::count())->toBe(1);
});

// ─── syncAllTier1Endorsements: cleanup orphans ────────────────────────────────

test('deletes orphaned records no longer present in VatEUD', function () {
    EndorsementActivity::create([
        'endorsement_id' => 99,
        'vatsim_id' => 9999999,
        'position' => 'EDDH_TWR',
        'activity_minutes' => 0.0,
        'created_at_vateud' => now(),
        'last_updated' => now(),
    ]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1(1, 1234567, 'EDDL_TWR')]);
    app()->instance(VatEudClientInterface::class, $client);
    bindActivityService();

    $this->artisan('endorsements:sync-activities')->assertExitCode(0);

    $this->assertDatabaseMissing('endorsement_activities', ['endorsement_id' => 99]);
    $this->assertDatabaseHas('endorsement_activities', ['endorsement_id' => 1]);
});

test('preserves records that are still present in VatEUD', function () {
    EndorsementActivity::create([
        'endorsement_id' => 1,
        'vatsim_id' => 1234567,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 50.0,
        'created_at_vateud' => now(),
        'last_updated' => now(),
    ]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1(1, 1234567, 'EDDL_TWR')]);
    app()->instance(VatEudClientInterface::class, $client);
    bindActivityService(50.0);

    $this->artisan('endorsements:sync-activities')->assertExitCode(0);

    expect(EndorsementActivity::where('endorsement_id', 1)->count())->toBe(1);
});

// ─── updateAllActivities: activity sync ───────────────────────────────────────

test('updates activity_minutes and last_activity_date from the activity service', function () {
    $lastDate = Carbon::parse('2025-10-15');

    $rec = EndorsementActivity::create([
        'endorsement_id' => 7,
        'vatsim_id' => 1234567,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0.0,
        'created_at_vateud' => now(),
        'last_updated' => now()->subHour(),
    ]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1(7, 1234567, 'EDDL_TWR')]);
    app()->instance(VatEudClientInterface::class, $client);
    bindActivityService(150.0, $lastDate);

    $this->artisan('endorsements:sync-activities')->assertExitCode(0);

    $rec->refresh();
    expect($rec->activity_minutes)->toBe(150.0);
    expect($rec->last_activity_date->format('Y-m-d'))->toBe('2025-10-15');
});

test('clears removal_date when activity reaches the minimum threshold', function () {
    $rec = EndorsementActivity::create([
        'endorsement_id' => 8,
        'vatsim_id' => 1234567,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 50.0,
        'removal_date' => now()->addDays(10),
        'removal_notified' => true,
        'created_at_vateud' => now(),
        'last_updated' => now(),
    ]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1(8, 1234567, 'EDDL_TWR')]);
    app()->instance(VatEudClientInterface::class, $client);
    bindActivityService(200.0); // above threshold of 180

    $this->artisan('endorsements:sync-activities')->assertExitCode(0);

    $rec->refresh();
    expect($rec->removal_date)->toBeNull();
    expect($rec->removal_notified)->toBeFalse();
});

test('does NOT clear removal_date when activity is still below threshold', function () {
    $removalDate = now()->addDays(10);

    $rec = EndorsementActivity::create([
        'endorsement_id' => 9,
        'vatsim_id' => 1234567,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 50.0,
        'removal_date' => $removalDate,
        'removal_notified' => true,
        'created_at_vateud' => now(),
        'last_updated' => now(),
    ]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1(9, 1234567, 'EDDL_TWR')]);
    app()->instance(VatEudClientInterface::class, $client);
    bindActivityService(90.0); // below threshold of 180

    $this->artisan('endorsements:sync-activities')->assertExitCode(0);

    $rec->refresh();
    expect($rec->removal_date)->not->toBeNull();
    expect($rec->removal_notified)->toBeTrue();
});

test('does not clear removal_date when there is no removal_date set', function () {
    $rec = EndorsementActivity::create([
        'endorsement_id' => 10,
        'vatsim_id' => 1234567,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0.0,
        'removal_date' => null,
        'created_at_vateud' => now(),
        'last_updated' => now(),
    ]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1(10, 1234567, 'EDDL_TWR')]);
    app()->instance(VatEudClientInterface::class, $client);
    bindActivityService(200.0);

    $this->artisan('endorsements:sync-activities')->assertExitCode(0);

    $rec->refresh();
    expect($rec->removal_date)->toBeNull(); // was null and stays null
});

// ─── Error handling ───────────────────────────────────────────────────────────

test('returns exit code 1 when an exception is thrown during sync', function () {
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andThrow(new \RuntimeException('VatEUD down'));
    app()->instance(VatEudClientInterface::class, $client);

    $this->artisan('endorsements:sync-activities')
        ->expectsOutputToContain('Error during sync')
        ->assertExitCode(1);
});
