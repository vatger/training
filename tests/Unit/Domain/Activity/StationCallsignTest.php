<?php

use App\Domain\Activity\Data\StationCallsign;

test('parses a standard three-part callsign', function () {
    $cs = StationCallsign::parse('EDGG_GIN_CTR');

    expect($cs->prefix)->toBe('EDGG')
        ->and($cs->infix)->toBe('GIN')
        ->and($cs->suffix)->toBe('CTR')
        ->and($cs->normalisedInfix())->toBe('GIN');
});

test('parses a two-part callsign with an empty infix', function () {
    $cs = StationCallsign::parse('EDDL_APP');

    expect($cs->prefix)->toBe('EDDL')
        ->and($cs->infix)->toBe('')
        ->and($cs->suffix)->toBe('APP')
        ->and($cs->isBandbox())->toBeTrue();
});

test('collapses empty middle segments from double underscores', function () {
    expect(StationCallsign::parse('EDGG__S_CTR')->infix)->toBe('S');
});

test('strips digits when normalising the infix', function () {
    expect(StationCallsign::parse('EDGG_S1_CTR')->normalisedInfix())->toBe('S')
        ->and(StationCallsign::parse('EDGG_C1H_CTR')->normalisedInfix())->toBe('CH')
        ->and(StationCallsign::parse('EDGG_CSH1_CTR')->normalisedInfix())->toBe('CSH');
});

test('uppercases and trims the raw input', function () {
    $cs = StationCallsign::parse('  eddf_c_twr ');

    expect($cs->raw)->toBe('EDDF_C_TWR')
        ->and($cs->icao())->toBe('EDDF');
});

test('icao is null when the prefix is not a four-letter code', function () {
    expect(StationCallsign::parse('LON_S_CTR')->icao())->toBeNull();
});
