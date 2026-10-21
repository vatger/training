<?php

use App\Integrations\Moodle\MoodleClientInterface;
use App\Models\Course;
use App\Models\FamiliarisationSector;
use App\Models\Role;
use App\Models\Subfamiliarisation;
use App\Models\SubfamiliarisationSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    subfamHttpFakeMoodle(true);
});

function subfamHttpFakeMoodle(bool $completed): void
{
    $client = new class($completed) implements MoodleClientInterface
    {
        public function __construct(private bool $completed) {}

        public function userExists(int $vatsimId): bool
        {
            return true;
        }

        public function getCourseCompletion(int $vatsimId, int $courseId): bool
        {
            return $this->completed;
        }

        public function getCourseName(int $courseId): ?string
        {
            return 'Moodle Course';
        }

        public function enrollUser(int $vatsimId, int $courseId): bool
        {
            return true;
        }
    };

    app()->instance(MoodleClientInterface::class, $client);
}

function subfamHttpMentor(Course $course): User
{
    $role = Role::firstOrCreate(['name' => 'EDGG Mentor'], ['description' => 'Mentor']);
    $user = User::factory()->create();
    $user->roles()->attach($role);
    $course->mentors()->attach($user->id);

    return $user;
}

function subfamHttpUnrelatedMentor(Course $course): User
{
    $role = Role::firstOrCreate(['name' => 'EDGG Mentor'], ['description' => 'Mentor']);
    $user = User::factory()->create();
    $user->roles()->attach($role);
    $otherCourse = Course::factory()->create(['familiarisation_sector_id' => null]);
    $otherCourse->mentors()->attach($user->id);

    return $user;
}

function subfamHttpSubfamiliarisation(): Subfamiliarisation
{
    $primary = FamiliarisationSector::create(['name' => 'EMS', 'fir' => 'EDMM']);
    $secondary = FamiliarisationSector::create(['name' => 'NH', 'fir' => 'EDMM']);

    return Subfamiliarisation::create([
        'name' => 'EMS / NH Maastricht Combined Control',
        'primary_sector_id' => $primary->id,
        'secondary_sector_id' => $secondary->id,
        'moodle_course_ids' => [101, 102],
    ]);
}

function subfamHttpFamCourse(Subfamiliarisation $subfamiliarisation): Course
{
    return Course::factory()->create(['familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);
}

// ─── create ────────────────────────────────────────────────────────────────────

test('unauthenticated user is redirected when visiting the create page', function () {
    $course = subfamHttpFamCourse(subfamHttpSubfamiliarisation());
    $trainee = User::factory()->create();

    $this->get(route('subfamiliarisation-sessions.create', ['traineeId' => $trainee->id, 'courseId' => $course->id]))
        ->assertRedirect();
});

test('a mentor unrelated to the course cannot open the create page', function () {
    $subfamiliarisation = subfamHttpSubfamiliarisation();
    $course = subfamHttpFamCourse($subfamiliarisation);
    $mentor = subfamHttpUnrelatedMentor($course);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->get(route('subfamiliarisation-sessions.create', ['traineeId' => $trainee->id, 'courseId' => $course->id]))
        ->assertForbidden();
});

test('a mentor of a course not tied to any subfamiliarisation is redirected back with an error', function () {
    subfamHttpSubfamiliarisation();
    $course = Course::factory()->create(['familiarisation_sector_id' => null]);
    $mentor = subfamHttpMentor($course);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->get(route('subfamiliarisation-sessions.create', ['traineeId' => $trainee->id, 'courseId' => $course->id]))
        ->assertRedirect(route('overview.index'))
        ->assertSessionHasErrors('error');
});

test('a mentor of the course granting the sector can open the create page', function () {
    $subfamiliarisation = subfamHttpSubfamiliarisation();
    $course = subfamHttpFamCourse($subfamiliarisation);
    $mentor = subfamHttpMentor($course);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->get(route('subfamiliarisation-sessions.create', ['traineeId' => $trainee->id, 'courseId' => $course->id]))
        ->assertSuccessful();
});

test('the create page reports an incomplete moodle status', function () {
    $subfamiliarisation = subfamHttpSubfamiliarisation();
    $course = subfamHttpFamCourse($subfamiliarisation);
    $mentor = subfamHttpMentor($course);
    $trainee = User::factory()->create();

    subfamHttpFakeMoodle(false);

    $this->actingAs($mentor)
        ->get(route('subfamiliarisation-sessions.create', ['traineeId' => $trainee->id, 'courseId' => $course->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('subfamiliarisations.0.moodleComplete', false));
});

test('the create page reports a complete moodle status', function () {
    $subfamiliarisation = subfamHttpSubfamiliarisation();
    $course = subfamHttpFamCourse($subfamiliarisation);
    $mentor = subfamHttpMentor($course);
    $trainee = User::factory()->create();

    subfamHttpFakeMoodle(true);

    $this->actingAs($mentor)
        ->get(route('subfamiliarisation-sessions.create', ['traineeId' => $trainee->id, 'courseId' => $course->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('subfamiliarisations.0.moodleComplete', true));
});

// ─── store ─────────────────────────────────────────────────────────────────────

test('unauthenticated user is redirected when storing a session', function () {
    $this->post(route('subfamiliarisation-sessions.store'), [])->assertRedirect();
});

test('a mentor unrelated to the course cannot log a session', function () {
    $subfamiliarisation = subfamHttpSubfamiliarisation();
    $course = subfamHttpFamCourse($subfamiliarisation);
    $mentor = subfamHttpUnrelatedMentor($course);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->post(route('subfamiliarisation-sessions.store'), [
            'subfamiliarisation_id' => $subfamiliarisation->id,
            'user_id' => $trainee->id,
            'course_id' => $course->id,
            'completed_at' => now()->toDateString(),
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('subfamiliarisation_sessions', ['user_id' => $trainee->id]);
});

test('a mentor of an unrelated course cannot log a session for a subfamiliarisation their course is not tied to', function () {
    $subfamiliarisation = subfamHttpSubfamiliarisation();
    $unrelatedCourse = Course::factory()->create(['familiarisation_sector_id' => null]);
    $mentor = subfamHttpMentor($unrelatedCourse);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->post(route('subfamiliarisation-sessions.store'), [
            'subfamiliarisation_id' => $subfamiliarisation->id,
            'user_id' => $trainee->id,
            'course_id' => $unrelatedCourse->id,
            'completed_at' => now()->toDateString(),
        ])
        ->assertForbidden();
});

test('a mentor of the granting course can log a session, recorded as themselves', function () {
    $subfamiliarisation = subfamHttpSubfamiliarisation();
    $course = subfamHttpFamCourse($subfamiliarisation);
    $mentor = subfamHttpMentor($course);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->post(route('subfamiliarisation-sessions.store'), [
            'subfamiliarisation_id' => $subfamiliarisation->id,
            'user_id' => $trainee->id,
            'course_id' => $course->id,
            'completed_at' => now()->toDateString(),
            'remarks' => 'Done well.',
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));

    $this->assertDatabaseHas('subfamiliarisation_sessions', [
        'subfamiliarisation_id' => $subfamiliarisation->id,
        'user_id' => $trainee->id,
        'mentor_id' => $mentor->id,
        'remarks' => 'Done well.',
    ]);
});

test('logging a session for a trainee who already has one updates it instead of duplicating', function () {
    $subfamiliarisation = subfamHttpSubfamiliarisation();
    $course = subfamHttpFamCourse($subfamiliarisation);
    $mentor = subfamHttpMentor($course);
    $trainee = User::factory()->create();

    SubfamiliarisationSession::create([
        'subfamiliarisation_id' => $subfamiliarisation->id,
        'user_id' => $trainee->id,
        'mentor_id' => $mentor->id,
        'completed_at' => now()->subDays(10),
    ]);

    $this->actingAs($mentor)
        ->post(route('subfamiliarisation-sessions.store'), [
            'subfamiliarisation_id' => $subfamiliarisation->id,
            'user_id' => $trainee->id,
            'course_id' => $course->id,
            'completed_at' => now()->toDateString(),
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));

    expect(SubfamiliarisationSession::where('user_id', $trainee->id)->count())->toBe(1);
});

test('a mentor cannot log a session for a trainee who has not completed the required moodle courses', function () {
    $subfamiliarisation = subfamHttpSubfamiliarisation();
    $course = subfamHttpFamCourse($subfamiliarisation);
    $mentor = subfamHttpMentor($course);
    $trainee = User::factory()->create();

    subfamHttpFakeMoodle(false);

    $this->actingAs($mentor)
        ->post(route('subfamiliarisation-sessions.store'), [
            'subfamiliarisation_id' => $subfamiliarisation->id,
            'user_id' => $trainee->id,
            'course_id' => $course->id,
            'completed_at' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('subfamiliarisation_id');

    $this->assertDatabaseMissing('subfamiliarisation_sessions', ['user_id' => $trainee->id]);
});

test('superuser can log a session for any course tied to a subfamiliarisation', function () {
    $subfamiliarisation = subfamHttpSubfamiliarisation();
    $course = subfamHttpFamCourse($subfamiliarisation);
    $trainee = User::factory()->create();

    $this->actingAs(User::factory()->create(['is_superuser' => true]))
        ->post(route('subfamiliarisation-sessions.store'), [
            'subfamiliarisation_id' => $subfamiliarisation->id,
            'user_id' => $trainee->id,
            'course_id' => $course->id,
            'completed_at' => now()->toDateString(),
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));
});

// ─── MentorOverviewController exposes subfamiliarisations on the loaded course ─

test('mentor overview page exposes subfamiliarisations for a course tied to one', function () {
    $subfamiliarisation = subfamHttpSubfamiliarisation();
    $course = subfamHttpFamCourse($subfamiliarisation);
    $mentor = subfamHttpMentor($course);

    $this->actingAs($mentor)
        ->get(route('overview.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('training/mentor-overview')
            ->where('courses', function ($courses) use ($course, $subfamiliarisation) {
                $loaded = collect($courses)->firstWhere('id', $course->id);

                return $loaded
                    && collect($loaded['subfamiliarisations'])->pluck('id')->contains($subfamiliarisation->id);
            })
        );
});

test('mentor overview page exposes no subfamiliarisations for an unrelated course', function () {
    subfamHttpSubfamiliarisation();
    $course = Course::factory()->create(['familiarisation_sector_id' => null]);
    $mentor = subfamHttpMentor($course);

    $this->actingAs($mentor)
        ->get(route('overview.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('training/mentor-overview')
            ->where('courses', function ($courses) use ($course) {
                $loaded = collect($courses)->firstWhere('id', $course->id);

                return $loaded && empty($loaded['subfamiliarisations']);
            })
        );
});
