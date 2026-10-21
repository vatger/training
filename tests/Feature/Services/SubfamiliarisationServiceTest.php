<?php

use App\Integrations\Moodle\MoodleClientInterface;
use App\Models\Course;
use App\Models\Familiarisation;
use App\Models\FamiliarisationSector;
use App\Models\Subfamiliarisation;
use App\Models\SubfamiliarisationSession;
use App\Models\User;
use App\Services\SubfamiliarisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

function fakeMoodleCompletions(array $completedByCourseId): void
{
    $client = new class($completedByCourseId) implements MoodleClientInterface
    {
        public function __construct(private array $completed) {}

        public function userExists(int $vatsimId): bool
        {
            return true;
        }

        public function getCourseCompletion(int $vatsimId, int $courseId): bool
        {
            return $this->completed[$courseId] ?? false;
        }

        public function getCourseName(int $courseId): ?string
        {
            return "Moodle Course {$courseId}";
        }

        public function enrollUser(int $vatsimId, int $courseId): bool
        {
            return true;
        }
    };

    app()->instance(MoodleClientInterface::class, $client);
    Cache::flush();
}

function makeSubfamiliarisation(array $moodleCourseIds = [101, 102]): Subfamiliarisation
{
    $primary = FamiliarisationSector::create(['name' => 'EMS', 'fir' => 'EDMM']);
    $secondary = FamiliarisationSector::create(['name' => 'NH', 'fir' => 'EDMM']);

    return Subfamiliarisation::create([
        'name' => 'EMS / NH Maastricht Combined Control',
        'primary_sector_id' => $primary->id,
        'secondary_sector_id' => $secondary->id,
        'moodle_course_ids' => $moodleCourseIds,
    ]);
}

function subfamiliarisationService(): SubfamiliarisationService
{
    return app(SubfamiliarisationService::class);
}

test('user with neither sector is not qualified and does not qualify via subfamiliarisation', function () {
    $subfamiliarisation = makeSubfamiliarisation();
    $user = User::factory()->create();
    fakeMoodleCompletions([101 => true, 102 => true]);

    expect(subfamiliarisationService()->isQualifiedForCombinedControl($user, $subfamiliarisation))->toBeFalse();
    expect(subfamiliarisationService()->qualifiesViaSubfamiliarisation($user, $subfamiliarisation))->toBeFalse();
});

test('user with both sectors is qualified without any extra requirements', function () {
    $subfamiliarisation = makeSubfamiliarisation();
    $user = User::factory()->create();
    Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);
    Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $subfamiliarisation->secondary_sector_id]);
    fakeMoodleCompletions([]);

    expect(subfamiliarisationService()->isQualifiedForCombinedControl($user, $subfamiliarisation))->toBeTrue();
});

test('user with one sector but incomplete moodle courses does not qualify', function () {
    $subfamiliarisation = makeSubfamiliarisation();
    $user = User::factory()->create();
    Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);
    fakeMoodleCompletions([101 => true, 102 => false]);

    SubfamiliarisationSession::create([
        'subfamiliarisation_id' => $subfamiliarisation->id,
        'user_id' => $user->id,
        'mentor_id' => User::factory()->create()->id,
        'completed_at' => now(),
    ]);

    expect(subfamiliarisationService()->qualifiesViaSubfamiliarisation($user, $subfamiliarisation))->toBeFalse();
    expect(subfamiliarisationService()->isQualifiedForCombinedControl($user, $subfamiliarisation))->toBeFalse();
});

test('user with one sector and completed moodle courses but no practical session does not qualify', function () {
    $subfamiliarisation = makeSubfamiliarisation();
    $user = User::factory()->create();
    Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);
    fakeMoodleCompletions([101 => true, 102 => true]);

    expect(subfamiliarisationService()->qualifiesViaSubfamiliarisation($user, $subfamiliarisation))->toBeFalse();
});

test('user with one sector, an arbitrary number of completed moodle courses, and a logged session qualifies', function () {
    $subfamiliarisation = makeSubfamiliarisation([201, 202, 203, 204, 205]);
    $user = User::factory()->create();
    Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);
    fakeMoodleCompletions([201 => true, 202 => true, 203 => true, 204 => true, 205 => true]);

    $mentor = User::factory()->create();
    SubfamiliarisationSession::create([
        'subfamiliarisation_id' => $subfamiliarisation->id,
        'user_id' => $user->id,
        'mentor_id' => $mentor->id,
        'completed_at' => now(),
    ]);

    expect(subfamiliarisationService()->qualifiesViaSubfamiliarisation($user, $subfamiliarisation))->toBeTrue();
    expect(subfamiliarisationService()->isQualifiedForCombinedControl($user, $subfamiliarisation))->toBeTrue();
    expect(subfamiliarisationService()->grantedSectorId($user, $subfamiliarisation))->toBe($subfamiliarisation->secondary_sector_id);
});

test('missing even one of many required moodle courses blocks qualification', function () {
    $subfamiliarisation = makeSubfamiliarisation([201, 202, 203]);
    $user = User::factory()->create();
    Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);
    fakeMoodleCompletions([201 => true, 202 => true, 203 => false]);

    SubfamiliarisationSession::create([
        'subfamiliarisation_id' => $subfamiliarisation->id,
        'user_id' => $user->id,
        'mentor_id' => User::factory()->create()->id,
        'completed_at' => now(),
    ]);

    expect(subfamiliarisationService()->qualifiesViaSubfamiliarisation($user, $subfamiliarisation))->toBeFalse();
});

test('admin and superuser can always log a session', function () {
    $subfamiliarisation = makeSubfamiliarisation();
    $admin = User::factory()->create(['is_admin' => true]);
    $superuser = User::factory()->create(['is_superuser' => true]);

    expect(subfamiliarisationService()->canLogSession($admin, $subfamiliarisation))->toBeTrue();
    expect(subfamiliarisationService()->canLogSession($superuser, $subfamiliarisation))->toBeTrue();
});

test('a mentor unrelated to the subfamiliarisation sectors cannot log a session', function () {
    $subfamiliarisation = makeSubfamiliarisation();
    $mentor = User::factory()->create();
    $unrelatedCourse = Course::factory()->create(['familiarisation_sector_id' => null]);
    $unrelatedCourse->mentors()->attach($mentor);

    expect(subfamiliarisationService()->canLogSession($mentor, $subfamiliarisation))->toBeFalse();
});

test('a mentor of the course granting the subfamiliarisation sector can log a session', function () {
    $subfamiliarisation = makeSubfamiliarisation();
    $mentor = User::factory()->create();
    $famCourse = Course::factory()->create(['familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);
    $famCourse->mentors()->attach($mentor);

    expect(subfamiliarisationService()->canLogSession($mentor, $subfamiliarisation))->toBeTrue();
});

test('a mentor of a course requiring the subfamiliarisation sector as a prerequisite can log a session', function () {
    $subfamiliarisation = makeSubfamiliarisation();
    $mentor = User::factory()->create();
    $ratingCourse = Course::factory()->create(['familiarisation_sector_id' => null]);
    $ratingCourse->requiredFamiliarisationSectors()->attach($subfamiliarisation->secondary_sector_id);
    $ratingCourse->mentors()->attach($mentor);

    expect(subfamiliarisationService()->canLogSession($mentor, $subfamiliarisation))->toBeTrue();
});

test('relevantSubfamiliarisationsForCourse finds the subfamiliarisation for the granting course', function () {
    $subfamiliarisation = makeSubfamiliarisation();
    $famCourse = Course::factory()->create(['familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);

    $relevant = subfamiliarisationService()->relevantSubfamiliarisationsForCourse($famCourse);

    expect($relevant->pluck('id')->all())->toBe([$subfamiliarisation->id]);
});

test('relevantSubfamiliarisationsForCourse finds the subfamiliarisation for a course requiring the sector', function () {
    $subfamiliarisation = makeSubfamiliarisation();
    $ratingCourse = Course::factory()->create(['familiarisation_sector_id' => null]);
    $ratingCourse->requiredFamiliarisationSectors()->attach($subfamiliarisation->secondary_sector_id);

    $relevant = subfamiliarisationService()->relevantSubfamiliarisationsForCourse($ratingCourse);

    expect($relevant->pluck('id')->all())->toBe([$subfamiliarisation->id]);
});

test('relevantSubfamiliarisationsForCourse returns empty for an unrelated course', function () {
    makeSubfamiliarisation();
    $unrelatedCourse = Course::factory()->create(['familiarisation_sector_id' => null]);

    expect(subfamiliarisationService()->relevantSubfamiliarisationsForCourse($unrelatedCourse))->toBeEmpty();
});
