<?php

/**
 * Unit tests for RemoveEndorsements — methods tested via reflection.
 * Complements Feature/Console/RemoveEndorsementsTest.php (artisan-level).
 * Focus: exact sendNotification() message format and vatger call arguments.
 */

use App\Console\Commands\RemoveEndorsements;
use App\Domain\Endorsement\Events\EndorsementRemoved;
use App\Integrations\VatEud\DTOs\Tier1EndorsementData;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Integrations\Vatger\VatgerClientInterface;
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

function rmEndMakeCommand(
    VatEudClientInterface $vatEud,
    VatgerClientInterface $vatger,
    VatsimActivityService $svc,
): RemoveEndorsements {
    return new RemoveEndorsements($vatEud, $vatger, $svc);
}

function rmEndSetIO(object $command): BufferedOutput
{
    $buffered = new BufferedOutput;
    $prop = new ReflectionProperty($command, 'output');
    $prop->setAccessible(true);
    $prop->setValue($command, new OutputStyle(new ArrayInput([]), $buffered));

    return $buffered;
}

function rmEndCall(object $cmd, string $method, mixed ...$args): mixed
{
    $m = new ReflectionMethod($cmd, $method);
    $m->setAccessible(true);

    return $m->invoke($cmd, ...$args);
}

function rmEndActivity(array $override = []): EndorsementActivity
{
    return EndorsementActivity::create(array_merge([
        'endorsement_id' => 99,
        'vatsim_id' => 1234567,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0.0,
        'removal_date' => now()->addDays(10),
        'removal_notified' => false,
        'created_at_vateud' => now(),
        'last_updated' => now(),
    ], $override));
}

function rmEndTier1(int $id, int $cid, string $position): Tier1EndorsementData
{
    return Tier1EndorsementData::fromApiResponse([
        'id' => $id, 'user_cid' => $cid, 'position' => $position,
        'facility' => 9, 'created_at' => '2025-01-01T00:00:00Z',
    ]);
}

function rmEndSilentSvc(float $minutes = 0.0): VatsimActivityService
{
    $svc = Mockery::mock(VatsimActivityService::class);
    $svc->shouldReceive('getEndorsementActivity')
        ->andReturn(['minutes' => $minutes, 'last_activity_date' => null]);

    return $svc;
}

// ─── sendNotification: exact message format ───────────────────────────────────

test('sendNotification sends to the correct VATSIM ID', function () {
    $rec = rmEndActivity(['vatsim_id' => 9876543, 'removal_date' => now()->addDays(14)]);

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')
        ->once()
        ->withArgs(fn ($id) => $id === 9876543)
        ->andReturn(['success' => true]);

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );

    rmEndCall($cmd, 'sendNotification', $rec);
});

test('sendNotification uses title "Endorsement Removal"', function () {
    $rec = rmEndActivity();

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')
        ->once()
        ->withArgs(fn ($id, $title) => $title === 'Endorsement Removal')
        ->andReturn(['success' => true]);

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );

    rmEndCall($cmd, 'sendNotification', $rec);
});

test('sendNotification message body contains the endorsement position', function () {
    $rec = rmEndActivity(['position' => 'EDDF_APP', 'removal_date' => now()->addDays(10)]);

    $capturedMessage = null;
    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')
        ->once()
        ->withArgs(function ($id, $title, $message) use (&$capturedMessage) {
            $capturedMessage = $message;

            return true;
        })
        ->andReturn(['success' => true]);

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );

    rmEndCall($cmd, 'sendNotification', $rec);

    expect($capturedMessage)->toContain('EDDF_APP');
});

test('sendNotification message body contains the formatted removal date', function () {
    $removalDate = Carbon::parse('2025-12-25');
    $rec = rmEndActivity(['removal_date' => $removalDate]);

    $capturedMessage = null;
    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')
        ->once()
        ->withArgs(function ($id, $title, $message) use (&$capturedMessage) {
            $capturedMessage = $message;

            return true;
        })
        ->andReturn(['success' => true]);

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );

    rmEndCall($cmd, 'sendNotification', $rec);

    expect($capturedMessage)->toContain('25.12.2025');
});

test('sendNotification message body mentions activity requirements', function () {
    $rec = rmEndActivity();

    $capturedMessage = null;
    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')
        ->withArgs(function ($id, $title, $message) use (&$capturedMessage) {
            $capturedMessage = $message;

            return true;
        })
        ->andReturn(['success' => true]);

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );

    rmEndCall($cmd, 'sendNotification', $rec);

    expect($capturedMessage)->toContain('activity');
});

test('sendNotification uses VATGER ATD as sourceName', function () {
    $rec = rmEndActivity();

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')
        ->once()
        ->withArgs(fn ($id, $title, $msg, $source) => $source === 'VATGER ATD')
        ->andReturn(['success' => true]);

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );

    rmEndCall($cmd, 'sendNotification', $rec);
});

test('sendNotification throws RuntimeException when vatger returns success=false', function () {
    $rec = rmEndActivity();

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')
        ->andReturn(['success' => false]);

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );

    expect(fn () => rmEndCall($cmd, 'sendNotification', $rec))
        ->toThrow(RuntimeException::class);
});

test('sendNotification does NOT throw when vatger returns success=true', function () {
    $rec = rmEndActivity();

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')->andReturn(['success' => true]);

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );

    // No exception should be thrown
    rmEndCall($cmd, 'sendNotification', $rec);
    expect(true)->toBeTrue();
});

// ─── sendRemovalNotifications: mark-as-notified logic ────────────────────────

test('sendRemovalNotifications marks record notified=true on success', function () {
    $rec = rmEndActivity(['removal_notified' => false]);

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')->andReturn(['success' => true]);

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );
    rmEndSetIO($cmd);

    rmEndCall($cmd, 'sendRemovalNotifications');

    $rec->refresh();
    expect($rec->removal_notified)->toBeTrue();
});

test('sendRemovalNotifications leaves notified=false when vatger fails', function () {
    $rec = rmEndActivity(['removal_notified' => false]);

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldReceive('sendNotification')->andReturn(['success' => false]);

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );
    rmEndSetIO($cmd);

    rmEndCall($cmd, 'sendRemovalNotifications');

    $rec->refresh();
    expect($rec->removal_notified)->toBeFalse();
});

test('sendRemovalNotifications skips records that are already notified', function () {
    $rec = rmEndActivity(['removal_notified' => true]);

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldNotReceive('sendNotification');

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );
    rmEndSetIO($cmd);

    rmEndCall($cmd, 'sendRemovalNotifications');
});

test('sendRemovalNotifications skips records with no removal_date', function () {
    rmEndActivity(['removal_date' => null, 'removal_notified' => false]);

    $vatger = Mockery::mock(VatgerClientInterface::class);
    $vatger->shouldNotReceive('sendNotification');

    $cmd = rmEndMakeCommand(
        Mockery::mock(VatEudClientInterface::class),
        $vatger,
        rmEndSilentSvc(),
    );
    rmEndSetIO($cmd);

    rmEndCall($cmd, 'sendRemovalNotifications');
});

// ─── processRemovals: decision logic ─────────────────────────────────────────

test('processRemovals fires EndorsementRemoved event when VatEUD delete succeeds', function () {
    rmEndActivity(['removal_date' => now()->subDay(), 'removal_notified' => true, 'endorsement_id' => 55]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([rmEndTier1(55, 1234567, 'EDDL_TWR')]);
    $client->shouldReceive('deleteTier1Endorsement')->with(55)->andReturn(true);

    $cmd = rmEndMakeCommand($client, Mockery::mock(VatgerClientInterface::class), rmEndSilentSvc(50.0));
    rmEndSetIO($cmd);

    rmEndCall($cmd, 'processRemovals');

    Event::assertDispatched(EndorsementRemoved::class);
});

test('processRemovals cancels removal and updates activity_minutes when activity sufficient', function () {
    $rec = rmEndActivity(['removal_date' => now()->subDay(), 'removal_notified' => true, 'endorsement_id' => 56, 'activity_minutes' => 10.0]);

    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getTier1Endorsements')->andReturn([rmEndTier1(56, 1234567, 'EDDL_TWR')]);

    $cmd = rmEndMakeCommand($client, Mockery::mock(VatgerClientInterface::class), rmEndSilentSvc(200.0));
    rmEndSetIO($cmd);

    rmEndCall($cmd, 'processRemovals');

    $rec->refresh();
    expect($rec->removal_date)->toBeNull();
    expect($rec->activity_minutes)->toBe(200.0);
    Event::assertNotDispatched(EndorsementRemoved::class);
});
