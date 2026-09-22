<?php

use App\Integrations\VatEud\VatEudClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['services.vateud.token' => 'fake-token']);
});

function fakeTier1Endorsement(int $vatsimId): array
{
    return [
        'id' => 1,
        'user_cid' => $vatsimId,
        'position' => 'EDDF_TWR',
        'facility' => 1,
        'created_at' => now()->toIso8601String(),
    ];
}

function fakeTier2Endorsement(int $vatsimId): array
{
    return [
        'id' => 2,
        'user_cid' => $vatsimId,
        'position' => 'EDDF_TWR',
        'created_at' => now()->toIso8601String(),
    ];
}

test('removeRosterAndEndorsements succeeds when roster and all endorsement deletions succeed', function () {
    $vatsimId = 1111111;

    Http::fake([
        '*/facility/roster/*' => Http::response([], 200),
        '*/facility/endorsements/tier-1/*' => Http::response([], 200),
        '*/facility/endorsements/tier-2/*' => Http::response([], 200),
        '*/facility/endorsements/tier-1' => Http::response(['data' => [fakeTier1Endorsement($vatsimId)]], 200),
        '*/facility/endorsements/tier-2' => Http::response(['data' => [fakeTier2Endorsement($vatsimId)]], 200),
    ]);

    $result = app(VatEudClient::class)->removeRosterAndEndorsements($vatsimId);

    expect($result)->toBeTrue();
});

test('removeRosterAndEndorsements fails when the roster removal call itself fails', function () {
    $vatsimId = 2222222;

    Http::fake([
        '*/facility/roster/*' => Http::response([], 500),
        '*/facility/endorsements/tier-1' => Http::response(['data' => []], 200),
        '*/facility/endorsements/tier-2' => Http::response(['data' => []], 200),
    ]);

    $result = app(VatEudClient::class)->removeRosterAndEndorsements($vatsimId);

    expect($result)->toBeFalse();
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'endorsements/tier-1/'));
});

test('removeRosterAndEndorsements still reports success when roster removal succeeds but endorsement cleanup fails', function () {
    // Regression test: the roster DELETE is the irreversible, audit-worthy
    // operation. A failed endorsement cleanup call afterward must not flip
    // the overall result to false, since callers use this return value to
    // decide whether to log/record that the removal happened at all.
    $vatsimId = 3333333;

    Http::fake([
        '*/facility/roster/*' => Http::response([], 200),
        '*/facility/endorsements/tier-1/*' => Http::response([], 500),
        '*/facility/endorsements/tier-2/*' => Http::response([], 200),
        '*/facility/endorsements/tier-1' => Http::response(['data' => [fakeTier1Endorsement($vatsimId)]], 200),
        '*/facility/endorsements/tier-2' => Http::response(['data' => [fakeTier2Endorsement($vatsimId)]], 200),
    ]);

    $result = app(VatEudClient::class)->removeRosterAndEndorsements($vatsimId);

    expect($result)->toBeTrue();
});
