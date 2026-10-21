<?php

use App\Filament\Resources\Subfamiliarisations\SubfamiliarisationResource;
use App\Filament\Resources\SubfamiliarisationSessions\Pages\CreateSubfamiliarisationSession;
use App\Filament\Resources\SubfamiliarisationSessions\SubfamiliarisationSessionResource;
use App\Integrations\Moodle\FakeMoodleClient;
use App\Integrations\Moodle\MoodleClientInterface;
use App\Models\Course;
use App\Models\FamiliarisationSector;
use App\Models\Permission;
use App\Models\Subfamiliarisation;
use App\Models\SubfamiliarisationSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(MoodleClientInterface::class, FakeMoodleClient::class);
});

function createSubfamiliarisation(): Subfamiliarisation
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

function fakeMoodleAllComplete(): void
{
    $client = new class implements MoodleClientInterface
    {
        public function userExists(int $vatsimId): bool
        {
            return true;
        }

        public function getCourseCompletion(int $vatsimId, int $courseId): bool
        {
            return true;
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

// ─── SubfamiliarisationResource (the rule config) ─────────────────────────────

test('a superuser can create a subfamiliarisation', function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));

    $this->get(SubfamiliarisationResource::getUrl('create'))->assertSuccessful();
});

test('the subfamiliarisation list renders the moodle course ids column without error', function () {
    createSubfamiliarisation();

    $this->actingAs(User::factory()->create(['is_superuser' => true]));

    $this->get(SubfamiliarisationResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee('101, 102');
});

test('a user with only the familiarisations edit permission cannot create a subfamiliarisation', function () {
    $permission = Permission::create(['name' => 'admin.familiarisations.edit']);
    $user = User::factory()->create();
    $user->permissions()->attach($permission);

    $this->actingAs($user);

    $this->get(SubfamiliarisationResource::getUrl('create'))->assertForbidden();
});

test('a user with the familiarisations view permission can view the subfamiliarisation list but not create', function () {
    $user = User::factory()->create();
    $user->permissions()->attach([
        Permission::create(['name' => 'admin.access'])->id,
        Permission::create(['name' => 'admin.familiarisations.view'])->id,
    ]);

    $this->actingAs($user);

    $this->get(SubfamiliarisationResource::getUrl('index'))->assertSuccessful();
    $this->get(SubfamiliarisationResource::getUrl('create'))->assertForbidden();
});

// ─── SubfamiliarisationSessionResource (the practical session log) ───────────

test('a mentor unrelated to the subfamiliarisation sectors cannot view or create sessions', function () {
    createSubfamiliarisation();
    $mentor = User::factory()->create();

    $this->actingAs($mentor);

    $this->get(SubfamiliarisationSessionResource::getUrl('index'))->assertForbidden();
    $this->get(SubfamiliarisationSessionResource::getUrl('create'))->assertForbidden();
});

test('a mentor of the course granting a subfamiliarisation sector can view and create sessions', function () {
    $subfamiliarisation = createSubfamiliarisation();
    $mentor = User::factory()->create();
    $mentor->permissions()->attach(Permission::create(['name' => 'admin.access'])->id);
    $famCourse = Course::factory()->create(['familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);
    $famCourse->mentors()->attach($mentor);

    $this->actingAs($mentor);

    $this->get(SubfamiliarisationSessionResource::getUrl('index'))->assertSuccessful();
    $this->get(SubfamiliarisationSessionResource::getUrl('create'))->assertSuccessful();
});

test('a qualifying mentor logging a session via the admin panel is always recorded as the mentor themselves', function () {
    $subfamiliarisation = createSubfamiliarisation();
    $mentor = User::factory()->create();
    $famCourse = Course::factory()->create(['familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);
    $famCourse->mentors()->attach($mentor);

    $trainee = User::factory()->create();
    $otherUser = User::factory()->create();

    fakeMoodleAllComplete();
    $this->actingAs($mentor);

    Livewire::test(CreateSubfamiliarisationSession::class)
        ->fillForm([
            'subfamiliarisation_id' => $subfamiliarisation->id,
            'user_id' => $trainee->id,
            'mentor_id' => $otherUser->id,
            'completed_at' => now()->toDateString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $session = SubfamiliarisationSession::first();
    expect($session->user_id)->toBe($trainee->id);
    expect($session->mentor_id)->toBe($mentor->id);
});

test('an admin can log a session on behalf of another mentor', function () {
    $subfamiliarisation = createSubfamiliarisation();
    $admin = User::factory()->create(['is_admin' => true]);
    $mentor = User::factory()->create();
    $trainee = User::factory()->create();

    fakeMoodleAllComplete();
    $this->actingAs($admin);

    Livewire::test(CreateSubfamiliarisationSession::class)
        ->fillForm([
            'subfamiliarisation_id' => $subfamiliarisation->id,
            'user_id' => $trainee->id,
            'mentor_id' => $mentor->id,
            'completed_at' => now()->toDateString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $session = SubfamiliarisationSession::first();
    expect($session->mentor_id)->toBe($mentor->id);
});

test('an admin cannot log a session for a trainee who has not completed the required moodle courses', function () {
    $subfamiliarisation = createSubfamiliarisation();
    $admin = User::factory()->create(['is_admin' => true]);
    $mentor = User::factory()->create();
    $trainee = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(CreateSubfamiliarisationSession::class)
        ->fillForm([
            'subfamiliarisation_id' => $subfamiliarisation->id,
            'user_id' => $trainee->id,
            'mentor_id' => $mentor->id,
            'completed_at' => now()->toDateString(),
        ])
        ->call('create')
        ->assertNotified();

    expect(SubfamiliarisationSession::count())->toBe(0);
});

test('an admin can override the moodle completion check to log a session anyway', function () {
    $subfamiliarisation = createSubfamiliarisation();
    $admin = User::factory()->create(['is_admin' => true]);
    $mentor = User::factory()->create();
    $trainee = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(CreateSubfamiliarisationSession::class)
        ->fillForm([
            'subfamiliarisation_id' => $subfamiliarisation->id,
            'user_id' => $trainee->id,
            'mentor_id' => $mentor->id,
            'completed_at' => now()->toDateString(),
            'moodle_override' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $session = SubfamiliarisationSession::first();
    expect($session)->not->toBeNull();
    expect($session->moodle_override)->toBeTrue();
});
