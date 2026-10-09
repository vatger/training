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

test('createSoloEndorsement extracts a readable string from a 422 validation failure array', function () {
    Http::fake([
        '*/facility/endorsements/solo' => Http::response([
            'error' => 'Validation failure.',
            'message' => ['The expire at field must be a date after tomorrow.'],
        ], 422),
    ]);

    $client = new VatEudClient;

    $result = $client->createSoloEndorsement(1234567, 'EDDF_TWR', now()->addDays(7)->toISOString(), 1439600);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBeString();
    expect($result['message'])->toBe('The expire at field must be a date after tomorrow.');
});

test('createSoloEndorsement flattens multiple validation failure messages into one string', function () {
    Http::fake([
        '*/facility/endorsements/solo' => Http::response([
            'error' => 'Validation failure.',
            'message' => [
                'The position field is required.',
                'The expire at field is required.',
            ],
        ], 422),
    ]);

    $client = new VatEudClient;

    $result = $client->createSoloEndorsement(1234567, 'EDDF_TWR', now()->addDays(7)->toISOString(), 1439600);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBeString();
    expect($result['message'])
        ->toContain('The position field is required.')
        ->toContain('The expire at field is required.');
});

test('createSoloEndorsement surfaces the plain-string message on a 403 permission failure', function () {
    Http::fake([
        '*/facility/endorsements/solo' => Http::response([
            'message' => 'Instructor lacking permission or user not a resident or a visiting controller in your vACC.',
        ], 403),
    ]);

    $client = new VatEudClient;

    $result = $client->createSoloEndorsement(1234567, 'EDDF_TWR', now()->addDays(7)->toISOString(), 1439600);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Instructor lacking permission or user not a resident or a visiting controller in your vACC.');
});

test('createSoloEndorsement falls back to a generic message when the response body is unparseable', function () {
    Http::fake([
        '*/facility/endorsements/solo' => Http::response('', 500),
    ]);

    $client = new VatEudClient;

    $result = $client->createSoloEndorsement(1234567, 'EDDF_TWR', now()->addDays(7)->toISOString(), 1439600);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Failed to create solo endorsement');
});

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
