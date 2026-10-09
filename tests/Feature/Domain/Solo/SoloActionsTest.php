<?php

use App\Domain\Solo\Actions\ExtendSoloEndorsement;
use App\Domain\Solo\Actions\GrantSoloEndorsement;
use App\Domain\Solo\Actions\RemoveSoloEndorsement;
use App\Domain\Solo\Events\SoloExtended;
use App\Domain\Solo\Events\SoloGranted;
use App\Domain\Solo\Events\SoloRemoved;
use App\Integrations\Moodle\FakeMoodleClient;
use App\Integrations\Moodle\MoodleClientInterface;
use App\Integrations\VatEud\DTOs\ExamResultData;
use App\Integrations\VatEud\DTOs\SoloEndorsementData;
use App\Integrations\VatEud\DTOs\UserExamsData;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatEudClientInterface::class, FakeVatEudClient::class);
    $this->app->bind(MoodleClientInterface::class, FakeMoodleClient::class);
    Cache::flush();
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Returns a VatEudClient fake that supplies a passing exam for the given exam ID
 * and has no existing solo endorsements.
 */
function fakeClientWithPassingExam(int $examId): FakeVatEudClient
{
    return new class($examId) extends FakeVatEudClient
    {
        public function __construct(private int $examId) {}

        public function getUserExams(int $vatsimId): UserExamsData
        {
            return new UserExamsData(
                results: [
                    new ExamResultData(
                        examId: $this->examId,
                        passed: true,
                        expiry: Carbon::now()->addYear(),
                    ),
                ],
                assignments: [],
            );
        }
    };
}

/**
 * Returns a VatEudClient fake that has a pre-existing solo for the given vatsim_id
 * and position, and supplies a passing exam for the position.
 */
function fakeClientWithExistingSolo(int $userCid, string $position, int $examId): FakeVatEudClient
{
    return new class($userCid, $position, $examId) extends FakeVatEudClient
    {
        public function __construct(
            private int $userCid,
            private string $position,
            private int $examId,
        ) {}

        public function getSoloEndorsements(): array
        {
            return [
                SoloEndorsementData::fromApiResponse([
                    'id' => 99,
                    'user_cid' => $this->userCid,
                    'position' => $this->position,
                    'facility' => 9,
                    'instructor_cid' => 1439600,
                    'position_days' => 30,
                    'expiry' => now()->addDays(30)->toISOString(),
                    'created_at' => now()->subDays(5)->toISOString(),
                ]),
            ];
        }

        public function getUserExams(int $vatsimId): UserExamsData
        {
            return new UserExamsData(
                results: [
                    new ExamResultData(
                        examId: $this->examId,
                        passed: true,
                        expiry: Carbon::now()->addYear(),
                    ),
                ],
                assignments: [],
            );
        }
    };
}

// ─── GrantSoloEndorsement ─────────────────────────────────────────────────────

test('GrantSoloEndorsement throws when moodle course not completed', function () {
    Event::fake();

    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    // FakeMoodleClient returns false for getCourseCompletion by default
    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [101],
    ]);

    try {
        app(GrantSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(30));
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['error'][0])
            ->toBe('Trainee has not completed all required Moodle courses');
    }
});

test('GrantSoloEndorsement throws when core theory not passed', function () {
    Event::fake();

    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    // No moodle requirement, but TWR position needs exam id 9
    // FakeVatEudClient returns empty exams by default
    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    try {
        app(GrantSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(30));
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['error'][0])
            ->toBe('Trainee has not passed the required core theory test');
    }
});

test('GrantSoloEndorsement throws when solo already exists for that position', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 1601613]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    // Provide passing exam AND existing solo
    $this->app->bind(
        VatEudClientInterface::class,
        fn () => fakeClientWithExistingSolo($trainee->vatsim_id, 'EDDL_TWR', 9),
    );
    Cache::flush();

    try {
        app(GrantSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(30));
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['error'][0])
            ->toBe('Trainee already has a solo endorsement for this position');
    }
});

test('GrantSoloEndorsement succeeds with no moodle, passing exam, no existing solo', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 9999997]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDF_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    // Provide passing TWR exam (id=9), no existing solo
    $this->app->bind(VatEudClientInterface::class, fn () => fakeClientWithPassingExam(9));
    Cache::flush();

    app(GrantSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(30));

    Event::assertDispatched(SoloGranted::class);
});

test('GrantSoloEndorsement fires SoloGranted with correct course and trainee', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 9999996]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDS_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => fakeClientWithPassingExam(9));
    Cache::flush();

    app(GrantSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(30));

    Event::assertDispatched(SoloGranted::class, function ($event) use ($course, $trainee) {
        return $event->course->id === $course->id
            && $event->trainee->id === $trainee->id;
    });
});

test('GrantSoloEndorsement succeeds for GND position with passing core theory exam', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 9999993]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDF_GND',
        'position' => 'GND',
        'moodle_course_ids' => [],
    ]);

    // GND requires exam id=6
    $this->app->bind(VatEudClientInterface::class, fn () => fakeClientWithPassingExam(6));
    Cache::flush();

    app(GrantSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(30));

    Event::assertDispatched(SoloGranted::class);
});

test('GrantSoloEndorsement throws a clear GCAP message when trainee has already used all 90 solo days', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 9999992, 'solo_days_used' => 90]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDF_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => fakeClientWithPassingExam(9));
    Cache::flush();

    try {
        app(GrantSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(7));
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['error'][0])->toContain('90/90')
            ->toContain('GCAP 7.3c');
    }

    Event::assertNotDispatched(SoloGranted::class);
});

test('GrantSoloEndorsement throws when the requested expiry would exceed the remaining 90-day budget', function () {
    Event::fake();

    // 79/90 used — 11 days remain, but the mentor asks for a 20-day solo.
    $trainee = User::factory()->create(['vatsim_id' => 9999991, 'solo_days_used' => 79]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDF_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => fakeClientWithPassingExam(9));
    Cache::flush();

    try {
        app(GrantSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(20));
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['error'][0])
            ->toContain('11 day(s) remain')
            ->toContain('79/90');
    }

    Event::assertNotDispatched(SoloGranted::class);
});

test('GrantSoloEndorsement succeeds when the requested expiry fits within the remaining 90-day budget', function () {
    Event::fake();

    // 70/90 used — 20 days remain, mentor asks for a 7-day solo, leaving 13
    // days spare (>= the 7-day minimum), so no dead zone is created.
    $trainee = User::factory()->create(['vatsim_id' => 9999990, 'solo_days_used' => 70]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDF_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => fakeClientWithPassingExam(9));
    Cache::flush();

    app(GrantSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(7));

    Event::assertDispatched(SoloGranted::class);
});

test('GrantSoloEndorsement throws when the requested duration would strand an unusable leftover under 7 days', function () {
    Event::fake();

    // 79/90 used — 11 days remain. A minimal 7-day grant would leave only 4
    // days spare, below the 7-day minimum any future solo needs — a dead zone.
    $trainee = User::factory()->create(['vatsim_id' => 9999989, 'solo_days_used' => 79]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDF_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => fakeClientWithPassingExam(9));
    Cache::flush();

    try {
        app(GrantSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(7));
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['error'][0])
            ->toContain('leave only 4 day(s)')
            ->toContain('entire 11 remaining day(s)');
    }

    Event::assertNotDispatched(SoloGranted::class);
});

test('GrantSoloEndorsement succeeds when granting the entire remaining budget even if it is under 14 days', function () {
    Event::fake();

    // 79/90 used — 11 days remain. Granting exactly the full 11 remaining days
    // leaves a 0-day leftover, which is fine (no future solo is stranded).
    $trainee = User::factory()->create(['vatsim_id' => 9999988, 'solo_days_used' => 79]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDF_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => fakeClientWithPassingExam(9));
    Cache::flush();

    app(GrantSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(11));

    Event::assertDispatched(SoloGranted::class);
});

// ─── ExtendSoloEndorsement ────────────────────────────────────────────────────

test('ExtendSoloEndorsement throws when no existing solo found', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 9999995]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    // FakeVatEudClient getSoloEndorsements() returns [] by default
    try {
        app(ExtendSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(60));
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['error'][0])
            ->toBe('No solo endorsement found for this trainee and position');
    }
});

test('ExtendSoloEndorsement succeeds when solo exists and fires SoloExtended', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 1601613]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $this->app->bind(
        VatEudClientInterface::class,
        fn () => new class extends FakeVatEudClient
        {
            public function getSoloEndorsements(): array
            {
                return [
                    SoloEndorsementData::fromApiResponse([
                        'id' => 88,
                        'user_cid' => 1601613,
                        'position' => 'EDDL_TWR',
                        'facility' => 9,
                        'instructor_cid' => 1439600,
                        'position_days' => 30,
                        'expiry' => now()->addDays(10)->toISOString(),
                        'created_at' => now()->subDays(20)->toISOString(),
                    ]),
                ];
            }
        },
    );
    Cache::flush();

    app(ExtendSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(60));

    Event::assertDispatched(SoloExtended::class);
});

test('ExtendSoloEndorsement fires SoloExtended with correct course and trainee', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 1601613]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $this->app->bind(
        VatEudClientInterface::class,
        fn () => new class extends FakeVatEudClient
        {
            public function getSoloEndorsements(): array
            {
                return [
                    SoloEndorsementData::fromApiResponse([
                        'id' => 88,
                        'user_cid' => 1601613,
                        'position' => 'EDDL_TWR',
                        'facility' => 9,
                        'instructor_cid' => 1439600,
                        'position_days' => 30,
                        'expiry' => now()->addDays(10)->toISOString(),
                        'created_at' => now()->subDays(20)->toISOString(),
                    ]),
                ];
            }
        },
    );
    Cache::flush();

    app(ExtendSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(60));

    Event::assertDispatched(SoloExtended::class, function ($event) use ($course, $trainee) {
        return $event->course->id === $course->id
            && $event->trainee->id === $trainee->id;
    });
});

test('ExtendSoloEndorsement throws when extension would exceed the 90-day budget, and does not delete the existing solo', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 1601613, 'solo_days_used' => 79]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $deleteWasCalled = false;

    $this->app->bind(
        VatEudClientInterface::class,
        fn () => new class($deleteWasCalled) extends FakeVatEudClient
        {
            public function __construct(private bool &$deleteWasCalled) {}

            public function getSoloEndorsements(): array
            {
                return [
                    SoloEndorsementData::fromApiResponse([
                        'id' => 88,
                        'user_cid' => 1601613,
                        'position' => 'EDDL_TWR',
                        'facility' => 9,
                        'instructor_cid' => 1439600,
                        'position_days' => 79,
                        'expiry' => now()->addDays(10)->toISOString(),
                        'created_at' => now()->subDays(79)->toISOString(),
                    ]),
                ];
            }

            public function deleteSoloEndorsement(int $soloId): bool
            {
                $this->deleteWasCalled = true;

                return true;
            }
        },
    );
    Cache::flush();

    try {
        // 11 days remain, but the mentor asks to extend by 20 days.
        app(ExtendSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(20));
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['error'][0])
            ->toContain('11 day(s) remain')
            ->toContain('79/90');
    }

    expect($deleteWasCalled)->toBeFalse();
    Event::assertNotDispatched(SoloExtended::class);
});

test('ExtendSoloEndorsement throws when extending would strand an unusable leftover under 7 days, and does not delete the existing solo', function () {
    Event::fake();

    // 79/90 used — 11 days remain. A minimal 7-day extension would leave only
    // 4 days spare, below the 7-day minimum any future solo needs.
    $trainee = User::factory()->create(['vatsim_id' => 1601614, 'solo_days_used' => 79]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $deleteWasCalled = false;

    $this->app->bind(
        VatEudClientInterface::class,
        fn () => new class($deleteWasCalled) extends FakeVatEudClient
        {
            public function __construct(private bool &$deleteWasCalled) {}

            public function getSoloEndorsements(): array
            {
                return [
                    SoloEndorsementData::fromApiResponse([
                        'id' => 89,
                        'user_cid' => 1601614,
                        'position' => 'EDDL_TWR',
                        'facility' => 9,
                        'instructor_cid' => 1439600,
                        'position_days' => 79,
                        'expiry' => now()->addDays(5)->toISOString(),
                        'created_at' => now()->subDays(79)->toISOString(),
                    ]),
                ];
            }

            public function deleteSoloEndorsement(int $soloId): bool
            {
                $this->deleteWasCalled = true;

                return true;
            }
        },
    );
    Cache::flush();

    try {
        app(ExtendSoloEndorsement::class)->execute($course, $trainee, $mentor, now()->addDays(7));
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['error'][0])
            ->toContain('leave only 4 day(s)')
            ->toContain('entire 11 remaining day(s)');
    }

    expect($deleteWasCalled)->toBeFalse();
    Event::assertNotDispatched(SoloExtended::class);
});

// ─── RemoveSoloEndorsement ────────────────────────────────────────────────────

test('RemoveSoloEndorsement throws when no existing solo found', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 9999994]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    // FakeVatEudClient getSoloEndorsements() returns [] by default
    try {
        app(RemoveSoloEndorsement::class)->execute($course, $trainee, $mentor);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['error'][0])
            ->toBe('No solo endorsement found for this trainee and position');
    }
});

test('RemoveSoloEndorsement throws when deleteSoloEndorsement returns false', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 1601613]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $this->app->bind(
        VatEudClientInterface::class,
        fn () => new class extends FakeVatEudClient
        {
            public function getSoloEndorsements(): array
            {
                return [
                    SoloEndorsementData::fromApiResponse([
                        'id' => 77,
                        'user_cid' => 1601613,
                        'position' => 'EDDL_TWR',
                        'facility' => 9,
                        'instructor_cid' => 1439600,
                        'position_days' => 30,
                        'expiry' => now()->addDays(5)->toISOString(),
                        'created_at' => now()->subDays(25)->toISOString(),
                    ]),
                ];
            }

            public function deleteSoloEndorsement(int $soloId): bool
            {
                return false;
            }
        },
    );
    Cache::flush();

    try {
        app(RemoveSoloEndorsement::class)->execute($course, $trainee, $mentor);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['error'][0])
            ->toBe('Failed to remove solo endorsement');
    }
});

test('RemoveSoloEndorsement succeeds and fires SoloRemoved', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 1601613]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $this->app->bind(
        VatEudClientInterface::class,
        fn () => new class extends FakeVatEudClient
        {
            public function getSoloEndorsements(): array
            {
                return [
                    SoloEndorsementData::fromApiResponse([
                        'id' => 77,
                        'user_cid' => 1601613,
                        'position' => 'EDDL_TWR',
                        'facility' => 9,
                        'instructor_cid' => 1439600,
                        'position_days' => 30,
                        'expiry' => now()->addDays(5)->toISOString(),
                        'created_at' => now()->subDays(25)->toISOString(),
                    ]),
                ];
            }
        },
    );
    Cache::flush();

    app(RemoveSoloEndorsement::class)->execute($course, $trainee, $mentor);

    Event::assertDispatched(SoloRemoved::class);
});

test('RemoveSoloEndorsement fires SoloRemoved with correct course and trainee', function () {
    Event::fake();

    $trainee = User::factory()->create(['vatsim_id' => 1601613]);
    $mentor = User::factory()->create();

    $course = Course::factory()->create([
        'solo_station' => 'EDDL_TWR',
        'position' => 'TWR',
        'moodle_course_ids' => [],
    ]);

    $this->app->bind(
        VatEudClientInterface::class,
        fn () => new class extends FakeVatEudClient
        {
            public function getSoloEndorsements(): array
            {
                return [
                    SoloEndorsementData::fromApiResponse([
                        'id' => 77,
                        'user_cid' => 1601613,
                        'position' => 'EDDL_TWR',
                        'facility' => 9,
                        'instructor_cid' => 1439600,
                        'position_days' => 30,
                        'expiry' => now()->addDays(5)->toISOString(),
                        'created_at' => now()->subDays(25)->toISOString(),
                    ]),
                ];
            }
        },
    );
    Cache::flush();

    app(RemoveSoloEndorsement::class)->execute($course, $trainee, $mentor);

    Event::assertDispatched(SoloRemoved::class, function ($event) use ($course, $trainee) {
        return $event->course->id === $course->id
            && $event->trainee->id === $trainee->id;
    });
});
