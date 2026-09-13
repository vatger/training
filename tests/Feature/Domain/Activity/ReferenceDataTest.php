<?php

use App\Domain\Activity\Reference\ActivityDataMirror;
use App\Domain\Activity\Reference\ReferenceDataRepository;
use App\Domain\Activity\Reference\StationRegistry;
use App\Integrations\Datahub\DatahubClientInterface;
use App\Integrations\Datahub\FakeDatahubClient;
use App\Integrations\VatglassesData\FakeVatglassesDataClient;
use App\Integrations\VatglassesData\VatglassesDataClientInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Cache::flush();
    Storage::fake('local');
    config()->set('activity.mirror.disk', 'local');
});

function syncActivityMirror(): array
{
    return app(ActivityDataMirror::class)->sync();
}

it('mirrors both data sources to storage and sets a version', function () {
    $result = syncActivityMirror();

    expect($result['changed'])->toBeTrue()
        ->and($result['files'])->toBeGreaterThan(1)
        ->and($result['version'])->not->toBe('empty');

    Storage::disk('local')->assertExists('activity/vatglasses/ed.json');
    Storage::disk('local')->assertExists('activity/datahub/edgg/ctr.json');

    expect(Cache::get(ActivityDataMirror::VERSION_CACHE_KEY))->toBe($result['version']);
});

it('parses VATGLASSES positions, sectors and airports', function () {
    syncActivityMirror();

    $reference = app(ReferenceDataRepository::class);

    expect($reference->positions())->toHaveKey('GIN')
        ->and($reference->position('GIN')->frequencyKhz)->toBe(124730)
        ->and($reference->position('GIN')->type)->toBe('CTR')
        ->and($reference->position('GIN')->pre)->toBe(['EDGG']);

    $ged = $reference->sectors()['GED'];
    expect($ged->ownerUids)->toBe(['GED', 'HEF', 'GIN', 'GINH', 'GC', 'GCA', 'GCH', 'GA', 'GAH', 'GCS', 'GCSH'])
        ->and($ged->primaryOwnerUid())->toBe('GED');

    expect($reference->airports())->toHaveKey('EDDF')
        ->and($reference->airports()['EDDF']->topdownUids)->toBe(['DFAS', 'DFAN', 'RUD', 'GCA', 'GCS']);

    expect($reference->groups())->toBe(['EDGG' => 'Langen']);
});

it('indexes datahub stations by logon and links them to VATGLASSES uids', function () {
    syncActivityMirror();

    $registry = app(StationRegistry::class);

    $gin = $registry->find('EDGG_GIN_CTR');
    expect($gin)->not->toBeNull()
        ->and($gin->positionUid)->toBe('GIN')
        ->and($gin->fir)->toBe('EDGG')
        ->and($gin->type)->toBe('CTR')
        ->and($gin->frequencyKhz)->toBe(124730)
        ->and($gin->requiredFamiliarisations)->toBe(['CH+NH']);

    // datahub logon infix (GIH) differs from the abbreviation (GINH).
    expect($registry->find('EDGG_GIH_CTR')->positionUid)->toBe('GINH');

    expect($registry->find('EDFH_TWR')->s1Twr)->toBeTrue()
        ->and($registry->find('EDFH_TWR')->isAerodrome())->toBeTrue();

    expect($registry->forFir('EDGG'))->not->toBeEmpty()
        ->and($registry->ofType('CTR'))->not->toBeEmpty();
});

it('produces a new version and reparses when the source data changes', function () {
    syncActivityMirror();
    $v1 = app(ReferenceDataRepository::class)->version();

    // Swap the fake payload for a mutated dataset.
    app()->forgetInstance(VatglassesDataClientInterface::class);
    app()->forgetInstance(ActivityDataMirror::class);

    $mutated = json_decode(file_get_contents(base_path('tests/Fixtures/Activity/vatglasses/ed.json')), true);
    $mutated['positions']['GIN']['frequency'] = '199.998';

    app()->instance(
        VatglassesDataClientInterface::class,
        (new FakeVatglassesDataClient)->fake(['vatglasses/ed.json' => json_encode($mutated)]),
    );
    app()->instance(DatahubClientInterface::class, new FakeDatahubClient);

    $result = app(ActivityDataMirror::class)->sync();
    app(ReferenceDataRepository::class)->flush();

    expect($result['version'])->not->toBe($v1)
        ->and($result['changed'])->toBeTrue()
        ->and(app(ReferenceDataRepository::class)->position('GIN')->frequencyKhz)->toBe(199998);
});
