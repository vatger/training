<?php

use App\Domain\Activity\Data\CallsignResolution;
use App\Domain\Activity\Reference\ActivityDataMirror;
use App\Domain\Activity\Resolver\CallsignResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Cache::flush();
    Storage::fake('local');
    config()->set('activity.mirror.disk', 'local');
    app(ActivityDataMirror::class)->sync();
});

function resolveCallsign(string $callsign, ?int $freqKhz = null): CallsignResolution
{
    return app(CallsignResolver::class)->resolve($callsign, $freqKhz);
}

it('resolves an exact datahub logon', function () {
    $result = resolveCallsign('EDGG_GIN_CTR');

    expect($result->positionUid)->toBe('GIN')
        ->and($result->via)->toBe('exact-logon');
});

it('resolves a logon whose infix differs from the abbreviation', function () {
    expect(resolveCallsign('EDGG_GIH_CTR')->positionUid)->toBe('GINH');
});

it('resolves relief/parallel-session digit variants by normalised infix', function () {
    expect(resolveCallsign('EDGG_GIN1_CTR')->positionUid)->toBe('GIN')
        ->and(resolveCallsign('EDGG_GIN2_CTR')->positionUid)->toBe('GIN');
});

it('resolves double-underscore and spacing variants', function () {
    expect(resolveCallsign('EDGG__SIG_CTR')->positionUid)->toBe('SIG');
});

it('prefers exact normalised infix over a looser subsequence match', function () {
    // "C1H" -> "CH" matches EDGG_CH_CTR exactly, not EDGG_CSH_CTR.
    expect(resolveCallsign('EDGG_C1H_CTR')->positionUid)->toBe('GCH');
    // "CSH1" -> "CSH" matches EDGG_CSH_CTR.
    expect(resolveCallsign('EDGG_CSH1_CTR')->positionUid)->toBe('GCSH');
});

it('falls back to frequency when the infix is unfamiliar', function () {
    $result = resolveCallsign('EDGG_XY_CTR', 124730);

    expect($result->positionUid)->toBe('GIN')
        ->and($result->via)->toBe('frequency');
});

it('resolves a uid-form login for a position with no dedicated datahub row', function () {
    expect(resolveCallsign('EDGG_GCA_CTR')->positionUid)->toBe('GCA');
});

it('treats a group bandbox login as a whole-group resolution', function () {
    $result = resolveCallsign('EDGG_CTR');

    expect($result->positionUid)->toBeNull()
        ->and($result->groupId)->toBe('EDGG')
        ->and($result->isResolved())->toBeTrue();
});

it('marks a genuinely unknown callsign as unresolved', function () {
    $result = resolveCallsign('LSAZ_C_CTR');

    expect($result->unresolved)->toBeTrue()
        ->and($result->isResolved())->toBeFalse();
});

it('applies configured aliases', function () {
    config()->set('activity.callsign_aliases', ['EDGG_LEGACY_CTR' => 'EDGG_GIN_CTR']);

    expect(resolveCallsign('EDGG_LEGACY_CTR')->positionUid)->toBe('GIN');
});

it('honours an expired alias window', function () {
    config()->set('activity.callsign_aliases', [
        'EDGG_OLD_CTR' => ['logon' => 'EDGG_GIN_CTR', 'until' => '2000-01-01'],
    ]);

    expect(resolveCallsign('EDGG_OLD_CTR')->unresolved)->toBeTrue();
});
