<?php

use App\Domain\Endorsement\Actions\GrantTier2Endorsement;
use App\Domain\Endorsement\Actions\MarkEndorsementForRemoval;
use App\Domain\Endorsement\Events\EndorsementMarkedForRemoval;
use App\Domain\Endorsement\Events\Tier2EndorsementGranted;
use App\Integrations\Moodle\FakeMoodleClient;
use App\Integrations\Moodle\MoodleClientInterface;
use App\Integrations\VatEud\DTOs\Tier1EndorsementData;
use App\Integrations\VatEud\DTOs\Tier2EndorsementData;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\EndorsementActivity;
use App\Models\Tier2Endorsement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatEudClientInterface::class, FakeVatEudClient::class);
    $this->app->bind(MoodleClientInterface::class, FakeMoodleClient::class);
    Cache::flush();
});

// ─── GrantTier2Endorsement ────────────────────────────────────────────────────

test('GrantTier2Endorsement throws if trainee already has that position', function () {
    $trainee = User::factory()->create(['vatsim_id' => 1601613]);

    $tier2 = Tier2Endorsement::create([
        'name' => 'Test Endorsement',
        'position' => 'EDDF_TWR',
        'moodle_course_id' => 0,
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => new class extends FakeVatEudClient
    {
        public function getTier2Endorsements(): array
        {
            return [
                Tier2EndorsementData::fromApiResponse([
                    'id' => 99,
                    'user_cid' => 1601613,
                    'position' => 'EDDF_TWR',
                    'facility' => 9,
                    'created_at' => now()->subYear()->toISOString(),
                ]),
            ];
        }
    });

    Cache::flush();

    expect(fn () => app(GrantTier2Endorsement::class)->execute($tier2, $trainee))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('GrantTier2Endorsement validation message says already have endorsement', function () {
    $trainee = User::factory()->create(['vatsim_id' => 1601613]);

    $tier2 = Tier2Endorsement::create([
        'name' => 'Test Endorsement',
        'position' => 'EDDF_TWR',
        'moodle_course_id' => 0,
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => new class extends FakeVatEudClient
    {
        public function getTier2Endorsements(): array
        {
            return [
                Tier2EndorsementData::fromApiResponse([
                    'id' => 99,
                    'user_cid' => 1601613,
                    'position' => 'EDDF_TWR',
                    'facility' => 9,
                    'created_at' => now()->subYear()->toISOString(),
                ]),
            ];
        }
    });

    Cache::flush();

    try {
        app(GrantTier2Endorsement::class)->execute($tier2, $trainee);
        $this->fail('Expected ValidationException');
    } catch (\Illuminate\Validation\ValidationException $e) {
        expect($e->errors()['endorsement'][0])->toBe('You already have this endorsement.');
    }
});

test('GrantTier2Endorsement throws if moodle_course_id set and course not completed', function () {
    $trainee = User::factory()->create();

    $tier2 = Tier2Endorsement::create([
        'name' => 'Moodle Required Endorsement',
        'position' => 'EDDF_GND',
        'moodle_course_id' => 42,
    ]);

    // FakeMoodleClient returns false by default for getCourseCompletion
    try {
        app(GrantTier2Endorsement::class)->execute($tier2, $trainee);
        $this->fail('Expected ValidationException');
    } catch (\Illuminate\Validation\ValidationException $e) {
        expect($e->errors()['endorsement'][0])
            ->toBe('You must complete the Moodle course before requesting this endorsement.');
    }
});

test('GrantTier2Endorsement succeeds when no moodle requirement and creates endorsement', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 9999999]);

    $tier2 = Tier2Endorsement::create([
        'name' => 'No Moodle Endorsement',
        'position' => 'EDDD_APP',
        'moodle_course_id' => 0,
    ]);

    // FakeVatEudClient returns no tier2 endorsements for this trainee's position
    app(GrantTier2Endorsement::class)->execute($tier2, $trainee);

    Event::assertDispatched(Tier2EndorsementGranted::class);
});

test('GrantTier2Endorsement fires Tier2EndorsementGranted with correct models', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 9999998]);

    $tier2 = Tier2Endorsement::create([
        'name' => 'Fire Event Endorsement',
        'position' => 'EDDD_TWR',
        'moodle_course_id' => 0,
    ]);

    app(GrantTier2Endorsement::class)->execute($tier2, $trainee);

    Event::assertDispatched(Tier2EndorsementGranted::class, function ($event) use ($tier2, $trainee) {
        return $event->tier2Endorsement->id === $tier2->id
            && $event->trainee->id === $trainee->id;
    });
});

// ─── MarkEndorsementForRemoval ────────────────────────────────────────────────

test('MarkEndorsementForRemoval throws if removal_date already set', function () {
    $actor = User::factory()->create();
    $trainee = User::factory()->create(['vatsim_id' => 1601613]);

    $activity = EndorsementActivity::create([
        'endorsement_id' => 1,
        'vatsim_id' => $trainee->vatsim_id,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0,
        'last_updated' => now(),
        'removal_date' => now()->addDays(10),
    ]);

    try {
        app(MarkEndorsementForRemoval::class)->execute($activity, $actor);
        $this->fail('Expected ValidationException');
    } catch (\Illuminate\Validation\ValidationException $e) {
        expect($e->errors()['endorsement'][0])
            ->toBe('This endorsement is already marked for removal.');
    }
});

test('MarkEndorsementForRemoval throws if endorsement not found in VatEud', function () {
    $actor = User::factory()->create();
    $trainee = User::factory()->create(['vatsim_id' => 1601613]);

    $activity = EndorsementActivity::create([
        'endorsement_id' => 999, // ID not returned by FakeVatEudClient
        'vatsim_id' => $trainee->vatsim_id,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0,
        'last_updated' => now(),
    ]);

    try {
        app(MarkEndorsementForRemoval::class)->execute($activity, $actor);
        $this->fail('Expected ValidationException');
    } catch (\Illuminate\Validation\ValidationException $e) {
        expect($e->errors()['endorsement'][0])
            ->toBe('Endorsement must be at least 6 months old before it can be removed.');
    }
});

test('MarkEndorsementForRemoval throws if endorsement younger than 6 months', function () {
    $actor = User::factory()->create();
    $trainee = User::factory()->create(['vatsim_id' => 1601613]);

    $activity = EndorsementActivity::create([
        'endorsement_id' => 1,
        'vatsim_id' => $trainee->vatsim_id,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0,
        'last_updated' => now(),
    ]);

    // FakeVatEudClient default returns endorsement id=1 with created_at in April 2025,
    // which is less than 6 months ago from today (2026-07-10).
    // Override to return one created TODAY so it's definitely too young.
    $this->app->bind(VatEudClientInterface::class, fn () => new class extends FakeVatEudClient
    {
        public function getTier1Endorsements(): array
        {
            return [
                Tier1EndorsementData::fromApiResponse([
                    'id' => 1,
                    'user_cid' => 1601613,
                    'position' => 'EDDL_TWR',
                    'facility' => 9,
                    'created_at' => now()->toISOString(),
                ]),
            ];
        }
    });

    Cache::flush();

    try {
        app(MarkEndorsementForRemoval::class)->execute($activity, $actor);
        $this->fail('Expected ValidationException');
    } catch (\Illuminate\Validation\ValidationException $e) {
        expect($e->errors()['endorsement'][0])
            ->toBe('Endorsement must be at least 6 months old before it can be removed.');
    }
});

test('MarkEndorsementForRemoval throws if activity_minutes meets minimum', function () {
    $actor = User::factory()->create();
    $trainee = User::factory()->create(['vatsim_id' => 1601613]);

    $activity = EndorsementActivity::create([
        'endorsement_id' => 1,
        'vatsim_id' => $trainee->vatsim_id,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 180,
        'last_updated' => now(),
    ]);

    // Endorsement must be old enough — override to 7 months ago
    $this->app->bind(VatEudClientInterface::class, fn () => new class extends FakeVatEudClient
    {
        public function getTier1Endorsements(): array
        {
            return [
                Tier1EndorsementData::fromApiResponse([
                    'id' => 1,
                    'user_cid' => 1601613,
                    'position' => 'EDDL_TWR',
                    'facility' => 9,
                    'created_at' => now()->subMonths(7)->toISOString(),
                ]),
            ];
        }
    });

    Cache::flush();

    try {
        app(MarkEndorsementForRemoval::class)->execute($activity, $actor);
        $this->fail('Expected ValidationException');
    } catch (\Illuminate\Validation\ValidationException $e) {
        expect($e->errors()['endorsement'][0])
            ->toBe('Endorsement has sufficient activity and cannot be marked for removal.');
    }
});

test('MarkEndorsementForRemoval success: sets removal_date 31 days from now', function () {
    Event::fake();

    $actor = User::factory()->create();
    $trainee = User::factory()->create(['vatsim_id' => 1601613]);

    $activity = EndorsementActivity::create([
        'endorsement_id' => 1,
        'vatsim_id' => $trainee->vatsim_id,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0,
        'last_updated' => now(),
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => new class extends FakeVatEudClient
    {
        public function getTier1Endorsements(): array
        {
            return [
                Tier1EndorsementData::fromApiResponse([
                    'id' => 1,
                    'user_cid' => 1601613,
                    'position' => 'EDDL_TWR',
                    'facility' => 9,
                    'created_at' => now()->subMonths(7)->toISOString(),
                ]),
            ];
        }
    });

    Cache::flush();

    app(MarkEndorsementForRemoval::class)->execute($activity, $actor);

    $fresh = $activity->fresh();
    expect($fresh->removal_date)->not->toBeNull();
    expect($fresh->removal_date->toDateString())
        ->toBe(now()->addDays(31)->toDateString());
    expect($fresh->removal_notified)->toBeFalse();
    expect(Carbon::createFromTimestamp(1)->toDateString())
        ->toBe($fresh->last_updated->toDateString());
});

test('MarkEndorsementForRemoval success: fires EndorsementMarkedForRemoval event', function () {
    Event::fake();

    $actor = User::factory()->create();
    $trainee = User::factory()->create(['vatsim_id' => 1601613]);

    $activity = EndorsementActivity::create([
        'endorsement_id' => 1,
        'vatsim_id' => $trainee->vatsim_id,
        'position' => 'EDDL_TWR',
        'activity_minutes' => 0,
        'last_updated' => now(),
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => new class extends FakeVatEudClient
    {
        public function getTier1Endorsements(): array
        {
            return [
                Tier1EndorsementData::fromApiResponse([
                    'id' => 1,
                    'user_cid' => 1601613,
                    'position' => 'EDDL_TWR',
                    'facility' => 9,
                    'created_at' => now()->subMonths(7)->toISOString(),
                ]),
            ];
        }
    });

    Cache::flush();

    app(MarkEndorsementForRemoval::class)->execute($activity, $actor);

    Event::assertDispatched(EndorsementMarkedForRemoval::class);
});
