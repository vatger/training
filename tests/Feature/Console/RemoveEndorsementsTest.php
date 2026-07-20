<?php

use App\Domain\Endorsement\Events\EndorsementRemoved;
use App\Integrations\VatEud\DTOs\Tier1EndorsementData;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Integrations\Vatger\VatgerClientInterface;
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

function makeTier1Entry(int $id, int $userCid, string $position): Tier1EndorsementData
{
    return Tier1EndorsementData::fromApiResponse([
        'id' => $id,
        'user_cid' => $userCid,
        'position' => $position,
        'facility' => 9,
        'created_at' => '2025-01-01T00:00:00Z',
    ]);
}

function pendingNotificationRecord(array $override = []): EndorsementActivity
{
    return EndorsementActivity::create(array_merge([
        'endorsement_id' => 1,
        'vatsim_id' => 1234567,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0.0,
        'removal_date' => now()->addDays(10),
        'removal_notified' => false,
        'created_at_vateud' => now(),
        'last_updated' => now(),
    ], $override));
}

function readyForRemovalRecord(array $override = []): EndorsementActivity
{
    return EndorsementActivity::create(array_merge([
        'endorsement_id' => 2,
        'vatsim_id' => 1234567,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0.0,
        'removal_date' => now()->subDay(),
        'removal_notified' => true,
        'created_at_vateud' => now(),
        'last_updated' => now(),
    ], $override));
}

function bindSilentVatEud(): void
{
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([]);
    $client->shouldReceive('deleteTier1Endorsement')->andReturn(true);
    app()->instance(VatEudClientInterface::class, $client);
}

function bindSilentVatger(): void
{
    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')->andReturn(['success' => true]);
    app()->instance(VatgerClientInterface::class, $vatger);
}

function bindSilentActivityService(float $minutes = 0.0): void
{
    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')->andReturn(['minutes' => $minutes, 'last_activity_date' => null]);
    app()->instance(VatsimActivityService::class, $svc);
}

// ─── sendRemovalNotifications: no pending ─────────────────────────────────────

test('outputs skip message when no pending removal notifications exist', function () {
    bindSilentVatEud();
    bindSilentVatger();
    bindSilentActivityService();

    $this->artisan('endorsements:remove')
        ->expectsOutputToContain('No pending removal notifications to send.')
        ->assertExitCode(0);
});

// ─── sendRemovalNotifications: sends notification ─────────────────────────────

test('sets removal_notified to true after sending notification', function () {
    $rec = pendingNotificationRecord(['endorsement_id' => 5, 'vatsim_id' => 1234567, 'position' => 'EDDL_TWR']);

    bindSilentVatEud();
    bindSilentActivityService();

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')
        ->once()
        ->with(1234567, 'Endorsement Removal', Mockery::type('string'), 'VATGER ATD', Mockery::any())
        ->andReturn(['success' => true]);
    app()->instance(VatgerClientInterface::class, $vatger);

    $this->artisan('endorsements:remove')->assertExitCode(0);

    $rec->refresh();
    expect($rec->removal_notified)->toBeTrue();
});

test('notification message includes position and removal date', function () {
    $removalDate = now()->addDays(31);
    pendingNotificationRecord([
        'endorsement_id' => 5,
        'position' => 'EDDF_APP',
        'removal_date' => $removalDate,
        'removal_notified' => false,
    ]);

    bindSilentVatEud();
    bindSilentActivityService();

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')
        ->once()
        ->withArgs(function ($vatsimId, $title, $message) use ($removalDate) {
            return str_contains($message, 'EDDF_APP')
                && str_contains($message, $removalDate->format('d.m.Y'));
        })
        ->andReturn(['success' => true]);
    app()->instance(VatgerClientInterface::class, $vatger);

    $this->artisan('endorsements:remove')->assertExitCode(0);
});

test('continues to next endorsement when notification send fails', function () {
    pendingNotificationRecord(['endorsement_id' => 10, 'vatsim_id' => 1111111]);
    pendingNotificationRecord(['endorsement_id' => 11, 'vatsim_id' => 2222222]);

    bindSilentVatEud();
    bindSilentActivityService();

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')
        ->twice()
        ->andReturn(['success' => false]);
    app()->instance(VatgerClientInterface::class, $vatger);

    // Both fail but command still completes
    $this->artisan('endorsements:remove')->assertExitCode(0);

    // Neither was marked notified
    expect(EndorsementActivity::where('removal_notified', true)->count())->toBe(0);
});

// ─── processRemovals: no removals due ─────────────────────────────────────────

test('outputs skip message when no endorsements are ready for removal', function () {
    // removal_notified = false means it won't appear in processRemovals
    pendingNotificationRecord();

    bindSilentVatEud();
    bindSilentVatger();
    bindSilentActivityService();

    $this->artisan('endorsements:remove')
        ->expectsOutputToContain('No endorsements ready for removal.')
        ->assertExitCode(0);
});

// ─── processRemovals: not found in VatEUD ────────────────────────────────────

test('deletes local record when endorsement is not found in VatEUD', function () {
    $rec = readyForRemovalRecord(['endorsement_id' => 20]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([]); // ID 20 not present
    app()->instance(VatEudClientInterface::class, $client);

    bindSilentVatger();
    bindSilentActivityService();

    $this->artisan('endorsements:remove')->assertExitCode(0);

    $this->assertDatabaseMissing('endorsement_activities', ['id' => $rec->id]);
});

// ─── processRemovals: sufficient activity cancels removal ────────────────────

test('cancels removal when endorsement now has sufficient activity', function () {
    $rec = readyForRemovalRecord(['endorsement_id' => 30, 'vatsim_id' => 1234567]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1Entry(30, 1234567, 'EDDL_TWR')]);
    app()->instance(VatEudClientInterface::class, $client);

    bindSilentVatger();

    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')->andReturn(['minutes' => 200.0, 'last_activity_date' => null]);
    app()->instance(VatsimActivityService::class, $svc);

    $this->artisan('endorsements:remove')->assertExitCode(0);

    $rec->refresh();
    expect($rec->removal_date)->toBeNull();
    expect($rec->removal_notified)->toBeFalse();
});

test('updates last_activity_date on the record when activity is checked', function () {
    $lastDate = Carbon::parse('2025-11-01');
    $rec = readyForRemovalRecord(['endorsement_id' => 31, 'vatsim_id' => 1234567]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1Entry(31, 1234567, 'EDDL_TWR')]);
    app()->instance(VatEudClientInterface::class, $client);

    bindSilentVatger();

    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')->andReturn(['minutes' => 200.0, 'last_activity_date' => $lastDate]);
    app()->instance(VatsimActivityService::class, $svc);

    $this->artisan('endorsements:remove')->assertExitCode(0);

    $rec->refresh();
    expect($rec->last_activity_date->format('Y-m-d'))->toBe('2025-11-01');
});

// ─── processRemovals: removes via VatEUD ──────────────────────────────────────

test('removes endorsement via VatEUD API when activity is insufficient', function () {
    $rec = readyForRemovalRecord(['endorsement_id' => 40, 'vatsim_id' => 1234567]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1Entry(40, 1234567, 'EDDL_TWR')]);
    $client->shouldReceive('deleteTier1Endorsement')->with(40)->once()->andReturn(true);
    app()->instance(VatEudClientInterface::class, $client);

    bindSilentVatger();
    bindSilentActivityService(50.0); // below 180

    $this->artisan('endorsements:remove')->assertExitCode(0);

    $this->assertDatabaseMissing('endorsement_activities', ['id' => $rec->id]);
});

test('fires EndorsementRemoved event when endorsement is successfully removed', function () {
    readyForRemovalRecord(['endorsement_id' => 41, 'vatsim_id' => 1234567]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1Entry(41, 1234567, 'EDDL_TWR')]);
    $client->shouldReceive('deleteTier1Endorsement')->andReturn(true);
    app()->instance(VatEudClientInterface::class, $client);

    bindSilentVatger();
    bindSilentActivityService(0.0);

    $this->artisan('endorsements:remove')->assertExitCode(0);

    Event::assertDispatched(EndorsementRemoved::class);
});

test('keeps local record when VatEUD delete returns false', function () {
    $rec = readyForRemovalRecord(['endorsement_id' => 50, 'vatsim_id' => 1234567]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1Entry(50, 1234567, 'EDDL_TWR')]);
    $client->shouldReceive('deleteTier1Endorsement')->andReturn(false);
    app()->instance(VatEudClientInterface::class, $client);

    bindSilentVatger();
    bindSilentActivityService(0.0);

    $this->artisan('endorsements:remove')
        ->expectsOutputToContain('Failed to remove')
        ->assertExitCode(0);

    $this->assertDatabaseHas('endorsement_activities', ['id' => $rec->id]);
});

test('does NOT fire EndorsementRemoved event when VatEUD delete fails', function () {
    readyForRemovalRecord(['endorsement_id' => 51, 'vatsim_id' => 1234567]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([makeTier1Entry(51, 1234567, 'EDDL_TWR')]);
    $client->shouldReceive('deleteTier1Endorsement')->andReturn(false);
    app()->instance(VatEudClientInterface::class, $client);

    bindSilentVatger();
    bindSilentActivityService(0.0);

    $this->artisan('endorsements:remove')->assertExitCode(0);

    Event::assertNotDispatched(EndorsementRemoved::class);
});

// ─── Full happy-path ─────────────────────────────────────────────────────────

test('returns exit code 0 when both notification and removal phases complete cleanly', function () {
    bindSilentVatEud();
    bindSilentVatger();
    bindSilentActivityService();

    $this->artisan('endorsements:remove')
        ->expectsOutputToContain('Endorsement removal process completed successfully.')
        ->assertExitCode(0);
});
