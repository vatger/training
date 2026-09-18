<?php

/**
 * Unit tests for SyncEndorsementActivities — methods tested via reflection.
 * Complements Feature/Console/SyncEndorsementActivitiesTest.php (artisan-level).
 * Focus: cleanupRemovedEndorsements ID matching, updateEndorsementActivity field
 * precision (eligible_since, null last_activity_date), exception isolation.
 */

use App\Console\Commands\SyncEndorsementActivities;
use App\Integrations\VatEud\DTOs\Tier1EndorsementData;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\EndorsementActivity;
use App\Services\VatsimActivityService;
use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function syncActMakeCommand(VatEudClientInterface $client, VatsimActivityService $svc): SyncEndorsementActivities
{
    return new SyncEndorsementActivities($client, $svc);
}

function syncActSetIO(object $command): BufferedOutput
{
    $buffered = new BufferedOutput;
    $prop = new ReflectionProperty($command, 'output');
    $prop->setAccessible(true);
    $prop->setValue($command, new OutputStyle(new ArrayInput([]), $buffered));

    return $buffered;
}

function syncActCallMethod(object $cmd, string $method, mixed ...$args): mixed
{
    $m = new ReflectionMethod($cmd, $method);
    $m->setAccessible(true);

    return $m->invoke($cmd, ...$args);
}

function syncActMakeTier1(int $id, int $cid, string $position): Tier1EndorsementData
{
    return Tier1EndorsementData::fromApiResponse([
        'id' => $id, 'user_cid' => $cid, 'position' => $position,
        'facility' => 9, 'created_at' => '2025-01-01T00:00:00Z',
    ]);
}

function syncActActivity(?Carbon $date = null, float $mins = 0.0): VatsimActivityService
{
    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')
        ->andReturn(['minutes' => $mins, 'last_activity_date' => $date]);
    $svc->shouldReceive('calculateEligibleSince')->andReturn(null);

    return $svc;
}

function syncActRecord(array $override = []): EndorsementActivity
{
    return EndorsementActivity::create(array_merge([
        'endorsement_id' => 1,
        'vatsim_id' => 1234567,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0.0,
        'created_at_vateud' => now(),
        'last_updated' => now()->subHour(),
    ], $override));
}

// ─── cleanupRemovedEndorsements ───────────────────────────────────────────────

test('cleanupRemovedEndorsements deletes records whose IDs are absent from the list', function () {
    syncActRecord(['endorsement_id' => 10]);
    syncActRecord(['endorsement_id' => 20]);
    syncActRecord(['endorsement_id' => 30]);

    $current = [syncActMakeTier1(10, 1234567, 'EDDL_TWR')]; // only ID 10 survives

    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, syncActActivity());
    syncActSetIO($cmd);

    syncActCallMethod($cmd, 'cleanupRemovedEndorsements', $current);

    expect(EndorsementActivity::where('endorsement_id', 10)->exists())->toBeTrue();
    expect(EndorsementActivity::where('endorsement_id', 20)->exists())->toBeFalse();
    expect(EndorsementActivity::where('endorsement_id', 30)->exists())->toBeFalse();
});

test('cleanupRemovedEndorsements keeps all records when all IDs still present', function () {
    syncActRecord(['endorsement_id' => 1]);
    syncActRecord(['endorsement_id' => 2]);

    $current = [
        syncActMakeTier1(1, 1234567, 'EDDL_TWR'),
        syncActMakeTier1(2, 7654321, 'EDDF_APP'),
    ];

    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, syncActActivity());
    syncActSetIO($cmd);

    syncActCallMethod($cmd, 'cleanupRemovedEndorsements', $current);

    expect(EndorsementActivity::count())->toBe(2);
});

test('cleanupRemovedEndorsements deletes all records when list is empty', function () {
    syncActRecord(['endorsement_id' => 1]);
    syncActRecord(['endorsement_id' => 2]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, syncActActivity());
    syncActSetIO($cmd);

    syncActCallMethod($cmd, 'cleanupRemovedEndorsements', []);

    expect(EndorsementActivity::count())->toBe(0);
});

test('cleanupRemovedEndorsements is a no-op when DB is already empty', function () {
    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, syncActActivity());
    syncActSetIO($cmd);

    syncActCallMethod($cmd, 'cleanupRemovedEndorsements', [syncActMakeTier1(99, 1234567, 'EDDL_TWR')]);

    expect(EndorsementActivity::count())->toBe(0);
});

// ─── updateEndorsementActivity: field precision ───────────────────────────────

test('updateEndorsementActivity updates activity_minutes and last_updated', function () {
    $rec = syncActRecord(['activity_minutes' => 0.0]);

    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')->andReturn(['minutes' => 155.5, 'last_activity_date' => null]);
    $svc->shouldReceive('calculateEligibleSince')->andReturn(null);

    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, $svc);

    syncActCallMethod($cmd, 'updateEndorsementActivity', $rec);

    $rec->refresh();
    expect($rec->activity_minutes)->toBe(155.5);
    expect($rec->last_updated)->not->toBeNull();
});

test('updateEndorsementActivity sets last_activity_date when service provides it', function () {
    $rec = syncActRecord();
    $date = Carbon::parse('2025-11-20');

    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')->andReturn(['minutes' => 90.0, 'last_activity_date' => $date]);
    $svc->shouldReceive('calculateEligibleSince')->andReturn(null);

    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, $svc);

    syncActCallMethod($cmd, 'updateEndorsementActivity', $rec);

    $rec->refresh();
    expect($rec->last_activity_date->format('Y-m-d'))->toBe('2025-11-20');
});

test('updateEndorsementActivity sets last_activity_date to null when service provides none', function () {
    $rec = syncActRecord(['last_activity_date' => now()]);

    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')->andReturn(['minutes' => 0.0, 'last_activity_date' => null]);
    $svc->shouldReceive('calculateEligibleSince')->andReturn(null);

    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, $svc);

    syncActCallMethod($cmd, 'updateEndorsementActivity', $rec);

    $rec->refresh();
    expect($rec->last_activity_date)->toBeNull();
});

test('updateEndorsementActivity stores eligible_since from the service', function () {
    $rec = syncActRecord();
    $eligibleDate = Carbon::parse('2025-06-01 00:00:00');

    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')->andReturn(['minutes' => 0.0, 'last_activity_date' => null]);
    $svc->shouldReceive('calculateEligibleSince')->andReturn($eligibleDate);

    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, $svc);

    syncActCallMethod($cmd, 'updateEndorsementActivity', $rec);

    $rec->refresh();
    expect($rec->eligible_since->format('Y-m-d'))->toBe('2025-06-01');
});

test('updateEndorsementActivity stores null eligible_since when service returns null', function () {
    $rec = syncActRecord();

    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, syncActActivity());

    syncActCallMethod($cmd, 'updateEndorsementActivity', $rec);

    $rec->refresh();
    expect($rec->eligible_since)->toBeNull();
});

test('updateEndorsementActivity clears removal_date when activity reaches threshold', function () {
    $rec = syncActRecord(['removal_date' => now()->addDays(5), 'removal_notified' => true]);

    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')->andReturn(['minutes' => 180.0, 'last_activity_date' => null]);
    $svc->shouldReceive('calculateEligibleSince')->andReturn(null);

    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, $svc);

    syncActCallMethod($cmd, 'updateEndorsementActivity', $rec);

    $rec->refresh();
    expect($rec->removal_date)->toBeNull();
    expect($rec->removal_notified)->toBeFalse();
});

test('updateEndorsementActivity does NOT clear removal_date at exactly threshold minus one minute', function () {
    $rec = syncActRecord(['removal_date' => now()->addDays(5), 'removal_notified' => true]);

    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')->andReturn(['minutes' => 179.0, 'last_activity_date' => null]);
    $svc->shouldReceive('calculateEligibleSince')->andReturn(null);

    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, $svc);

    syncActCallMethod($cmd, 'updateEndorsementActivity', $rec);

    $rec->refresh();
    expect($rec->removal_date)->not->toBeNull();
    expect($rec->removal_notified)->toBeTrue();
});

test('updateEndorsementActivity does not crash when service throws an exception', function () {
    $rec = syncActRecord();

    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')->andThrow(new RuntimeException('API error'));

    $client = Mockery::mock(VatEudClientInterface::class);
    $cmd = syncActMakeCommand($client, $svc);

    // Exception is caught internally — should not propagate
    syncActCallMethod($cmd, 'updateEndorsementActivity', $rec);
    expect(true)->toBeTrue();
});
