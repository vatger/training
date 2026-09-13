<?php

use App\Domain\Activity\Reference\ActivityDataMirror;
use App\Domain\Activity\Reference\SectorOwnershipIndex;
use App\Domain\Activity\Resolver\StationActivityScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Cache::flush();
    Storage::fake('local');
    config()->set('activity.mirror.disk', 'local');
    app(ActivityDataMirror::class)->sync();
});

it('maps a uid to the sectors it primarily owns', function () {
    $index = app(SectorOwnershipIndex::class);

    expect($index->primarySectorKeysFor('GIN'))->toBe(['GIN'])
        ->and($index->primarySectorKeysFor('HEF'))->toBe(['HEF'])
        ->and($index->primarySectorKeysFor('GN'))->toBe([]);
});

it('ranks top-down ownership depth within a sector', function () {
    $index = app(SectorOwnershipIndex::class);

    // GED owner order: GED, HEF, GIN, GINH, ...
    expect($index->rank('GED', 'GED'))->toBe(0)
        ->and($index->rank('GED', 'HEF'))->toBe(1)
        ->and($index->rank('GED', 'GIN'))->toBe(2)
        ->and($index->rank('GED', 'GN'))->toBeNull();
});

it('lists every sector a uid can top-down', function () {
    $index = app(SectorOwnershipIndex::class);

    expect($index->sectorKeysOwnedBy('GIN'))
        ->toContain('GIN', 'HEF', 'GED', 'SIG', 'TAU');
});

it('groups sectors by their group id', function () {
    expect(app(SectorOwnershipIndex::class)->groupSectorKeys('EDGG'))
        ->toContain('GIN', 'HEF', 'GED', 'SIG', 'TAU', 'GINH');
});

it('builds an activity scope for a CTR station from its primary sectors', function () {
    $scope = app(StationActivityScope::class)->forLogon('EDGG_GIN_CTR');

    expect($scope->positionUid)->toBe('GIN')
        ->and($scope->primarySectorKeys)->toBe(['GIN'])
        ->and($scope->airportIcao)->toBeNull()
        ->and($scope->selfMatchLogons)->toContain('EDGG_GIN_CTR')
        ->and($scope->isEmpty())->toBeFalse();
});

it('builds an activity scope for an aerodrome station from its airport', function () {
    $scope = app(StationActivityScope::class)->forLogon('EDDF_TWR');

    expect($scope->primarySectorKeys)->toBe([])
        ->and($scope->airportIcao)->toBe('EDDF')
        ->and($scope->selfMatchLogons)->toContain('EDDF_TWR');
});
