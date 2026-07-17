<?php

use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Integrations\Vatger\FakeVatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\Course;
use App\Models\Cpt;
use App\Models\Examiner;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->app->bind(VatEudClientInterface::class, FakeVatEudClient::class);
    $this->app->bind(VatgerClientInterface::class, FakeVatgerClient::class);
    Storage::fake('private');

    // Fake all events except Cpt/CptLog Eloquent hooks so the saving hook
    // that sets `confirmed` still runs, while LogsActivity on Course is suppressed.
    Event::fakeExcept([
        'eloquent.creating: App\Models\Cpt',
        'eloquent.created: App\Models\Cpt',
        'eloquent.updating: App\Models\Cpt',
        'eloquent.updated: App\Models\Cpt',
        'eloquent.saving: App\Models\Cpt',
        'eloquent.saved: App\Models\Cpt',
        'eloquent.creating: App\Models\CptLog',
        'eloquent.created: App\Models\CptLog',
        'eloquent.updating: App\Models\CptLog',
        'eloquent.updated: App\Models\CptLog',
        'eloquent.saving: App\Models\CptLog',
        'eloquent.saved: App\Models\CptLog',
    ]);
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cptHttpSuperuser(): User
{
    return User::factory()->superuser()->create();
}

function cptHttpMentor(Course $course): User
{
    $role = Role::firstOrCreate(['name' => 'EDGG Mentor'], ['description' => 'Mentor']);
    $user = User::factory()->create();
    $user->roles()->attach($role);
    $course->mentors()->attach($user);
    return $user;
}

function cptHttpTwrCourse(): Course
{
    return Course::factory()->create([
        'type'         => 'RTG',
        'position'     => 'TWR',
        'solo_station' => 'EDDF_TWR',
    ]);
}

function cptHttpMakeExaminerFor(User $user, string $position = 'TWR'): Examiner
{
    return Examiner::create([
        'user_id'   => $user->id,
        'callsign'  => 'EX' . $user->id . 'X',
        'positions' => [$position],
    ]);
}

function cptHttpRecord(Course $course, array $attrs = []): Cpt
{
    return Cpt::create(array_merge([
        'course_id'   => $course->id,
        'trainee_id'  => User::factory()->create()->id,
        'date'        => now()->addDays(3),
        'examiner_id' => null,
        'local_id'    => null,
    ], $attrs));
}

// ─── CptController: index ─────────────────────────────────────────────────────

test('unauthenticated user is redirected from cpt index', function () {
    $this->get(route('cpt.index'))->assertRedirect();
});

test('non-mentor user is forbidden from cpt index', function () {
    $this->actingAs(User::factory()->create())->get(route('cpt.index'))->assertForbidden();
});

test('superuser can view the cpt index', function () {
    $this->actingAs(cptHttpSuperuser())->get(route('cpt.index'))->assertOk();
});

test('mentor can view the cpt index', function () {
    $course = cptHttpTwrCourse();
    $mentor = cptHttpMentor($course);

    $this->actingAs($mentor)->get(route('cpt.index'))->assertOk();
});

// ─── CptController: create page ───────────────────────────────────────────────

test('unauthenticated user is redirected from cpt create page', function () {
    $this->get(route('cpt.create'))->assertRedirect();
});

test('superuser can access the cpt create page', function () {
    $this->actingAs(cptHttpSuperuser())->get(route('cpt.create'))->assertOk();
});

// ─── CptController: store ─────────────────────────────────────────────────────

test('unauthenticated user cannot create a cpt', function () {
    $this->post(route('cpt.store'), [])->assertRedirect();
});

test('non-mentor user cannot create a cpt', function () {
    $this->actingAs(User::factory()->create())->post(route('cpt.store'), [])->assertForbidden();
});

test('store fails validation when required fields are missing', function () {
    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.store'), [])
        ->assertSessionHasErrors(['course_id', 'trainee_id', 'date']);
});

test('store fails validation when date is in the past', function () {
    $course  = cptHttpTwrCourse();
    $trainee = User::factory()->create();

    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.store'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
            'date'       => now()->subDay()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('date');
});

test('superuser can create a cpt without examiner or local', function () {
    $course  = cptHttpTwrCourse();
    $trainee = User::factory()->create();

    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.store'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
            'date'       => now()->addDays(2)->toDateTimeString(),
        ])
        ->assertRedirect(route('cpt.index'));

    $this->assertDatabaseHas('cpts', [
        'course_id'  => $course->id,
        'trainee_id' => $trainee->id,
        'confirmed'  => false,
    ]);
});

test('creating a cpt with both examiner and local results in confirmed true', function () {
    $course       = cptHttpTwrCourse();
    $trainee      = User::factory()->create();
    $examinerUser = User::factory()->create();
    cptHttpMakeExaminerFor($examinerUser, 'TWR');
    $localUser = cptHttpMentor($course);

    // Date within 36h so a course mentor is allowed to be examiner too
    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.store'), [
            'course_id'   => $course->id,
            'trainee_id'  => $trainee->id,
            'date'        => now()->addHours(20)->toDateTimeString(),
            'examiner_id' => $examinerUser->id,
            'local_id'    => $localUser->id,
        ])
        ->assertRedirect(route('cpt.index'));

    $this->assertDatabaseHas('cpts', [
        'course_id'  => $course->id,
        'trainee_id' => $trainee->id,
        'confirmed'  => true,
    ]);
});

test('store rejects when trainee and examiner are the same user', function () {
    $course  = cptHttpTwrCourse();
    $trainee = User::factory()->create();
    cptHttpMakeExaminerFor($trainee, 'TWR');

    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.store'), [
            'course_id'   => $course->id,
            'trainee_id'  => $trainee->id,
            'date'        => now()->addDays(2)->toDateTimeString(),
            'examiner_id' => $trainee->id,
        ])
        ->assertSessionHasErrors('examiner_id');
});

test('store rejects when examiner user has no examiner profile', function () {
    $course      = cptHttpTwrCourse();
    $trainee     = User::factory()->create();
    $nonExaminer = User::factory()->create();

    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.store'), [
            'course_id'   => $course->id,
            'trainee_id'  => $trainee->id,
            'date'        => now()->addDays(2)->toDateTimeString(),
            'examiner_id' => $nonExaminer->id,
        ])
        ->assertSessionHasErrors('examiner_id');
});

test('store rejects when examiner is not authorized for the course position', function () {
    $course       = cptHttpTwrCourse(); // TWR position
    $trainee      = User::factory()->create();
    $examinerUser = User::factory()->create();
    cptHttpMakeExaminerFor($examinerUser, 'APP'); // APP examiner, not TWR

    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.store'), [
            'course_id'   => $course->id,
            'trainee_id'  => $trainee->id,
            'date'        => now()->addDays(2)->toDateTimeString(),
            'examiner_id' => $examinerUser->id,
        ])
        ->assertSessionHasErrors('examiner_id');
});

test('store rejects when local is not a mentor for the course', function () {
    $course    = cptHttpTwrCourse();
    $trainee   = User::factory()->create();
    $nonMentor = User::factory()->create();

    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.store'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
            'date'       => now()->addDays(2)->toDateTimeString(),
            'local_id'   => $nonMentor->id,
        ])
        ->assertSessionHasErrors('local_id');
});

// ─── CptController: destroy ───────────────────────────────────────────────────

test('unauthenticated user cannot delete a cpt', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $this->delete(route('cpt.destroy', $cpt))->assertRedirect();
});

test('non-mentor cannot delete a cpt', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $this->actingAs(User::factory()->create())->delete(route('cpt.destroy', $cpt))->assertForbidden();
});

test('superuser can delete a pending cpt', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);

    $this->actingAs(cptHttpSuperuser())->delete(route('cpt.destroy', $cpt));

    $this->assertDatabaseMissing('cpts', ['id' => $cpt->id]);
});

test('course mentor can delete a cpt for their course', function () {
    $course = cptHttpTwrCourse();
    $mentor = cptHttpMentor($course);
    $cpt    = cptHttpRecord($course);

    $this->actingAs($mentor)->delete(route('cpt.destroy', $cpt));

    $this->assertDatabaseMissing('cpts', ['id' => $cpt->id]);
});

test('mentor not assigned to the course cannot delete that cpt', function () {
    $course      = cptHttpTwrCourse();
    $otherCourse = cptHttpTwrCourse();
    $cpt         = cptHttpRecord($course);
    $otherMentor = cptHttpMentor($otherCourse);

    $this->actingAs($otherMentor)
        ->delete(route('cpt.destroy', $cpt))
        ->assertSessionHasErrors('error');

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id]);
});

test('cannot delete a graded cpt', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course, ['passed' => true]);

    $this->actingAs(cptHttpSuperuser())
        ->delete(route('cpt.destroy', $cpt))
        ->assertSessionHasErrors('error');

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id]);
});

// ─── CptAssignmentController: join-examiner ───────────────────────────────────

test('unauthenticated user cannot join as examiner', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $this->post(route('cpt.join-examiner', $cpt))->assertRedirect();
});

test('non-mentor cannot join as examiner', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $this->actingAs(User::factory()->create())->post(route('cpt.join-examiner', $cpt))->assertForbidden();
});

test('user without examiner profile cannot join as examiner', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);

    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.join-examiner', $cpt))
        ->assertSessionHasErrors('error');
});

test('examiner without the correct position cannot join as examiner', function () {
    $course       = cptHttpTwrCourse();
    $cpt          = cptHttpRecord($course);
    $examinerUser = cptHttpSuperuser();
    cptHttpMakeExaminerFor($examinerUser, 'APP'); // wrong position

    $this->actingAs($examinerUser)
        ->post(route('cpt.join-examiner', $cpt))
        ->assertSessionHasErrors('error');
});

test('eligible examiner can join as examiner', function () {
    $course       = cptHttpTwrCourse();
    $cpt          = cptHttpRecord($course);
    $examinerUser = cptHttpSuperuser();
    cptHttpMakeExaminerFor($examinerUser, 'TWR');

    $this->actingAs($examinerUser)
        ->post(route('cpt.join-examiner', $cpt))
        ->assertRedirect();

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'examiner_id' => $examinerUser->id]);
});

test('joining as examiner confirms the cpt when a local is already assigned', function () {
    $course       = cptHttpTwrCourse();
    $localUser    = User::factory()->create();
    $cpt          = cptHttpRecord($course, ['local_id' => $localUser->id]);
    $examinerUser = cptHttpSuperuser();
    cptHttpMakeExaminerFor($examinerUser, 'TWR');

    $this->actingAs($examinerUser)->post(route('cpt.join-examiner', $cpt));

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'confirmed' => true]);
});

test('cannot join as examiner when already the examiner', function () {
    $course       = cptHttpTwrCourse();
    $examinerUser = cptHttpSuperuser();
    cptHttpMakeExaminerFor($examinerUser, 'TWR');
    $cpt = cptHttpRecord($course, ['examiner_id' => $examinerUser->id]);

    $this->actingAs($examinerUser)
        ->post(route('cpt.join-examiner', $cpt))
        ->assertSessionHasErrors('error');
});

test('cannot join as examiner when already the local contact', function () {
    $course       = cptHttpTwrCourse();
    $user         = cptHttpSuperuser();
    cptHttpMakeExaminerFor($user, 'TWR');
    $cpt = cptHttpRecord($course, ['local_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('cpt.join-examiner', $cpt))
        ->assertSessionHasErrors('error');
});

// ─── CptAssignmentController: leave-examiner ─────────────────────────────────

test('examiner can leave their assignment', function () {
    $course       = cptHttpTwrCourse();
    $examinerUser = cptHttpSuperuser();
    $cpt          = cptHttpRecord($course, ['examiner_id' => $examinerUser->id]);

    $this->actingAs($examinerUser)
        ->post(route('cpt.leave-examiner', $cpt))
        ->assertRedirect();

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'examiner_id' => null]);
});

test('non-examiner cannot leave as examiner', function () {
    $course    = cptHttpTwrCourse();
    $cpt       = cptHttpRecord($course);
    $otherUser = cptHttpSuperuser();

    $this->actingAs($otherUser)
        ->post(route('cpt.leave-examiner', $cpt))
        ->assertSessionHasErrors('error');
});

// ─── CptAssignmentController: join-local ─────────────────────────────────────

test('unauthenticated user cannot join as local contact', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $this->post(route('cpt.join-local', $cpt))->assertRedirect();
});

test('non-mentor cannot join as local contact', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $this->actingAs(User::factory()->create())->post(route('cpt.join-local', $cpt))->assertForbidden();
});

test('course mentor can join as local contact', function () {
    $course = cptHttpTwrCourse();
    $mentor = cptHttpMentor($course);
    $cpt    = cptHttpRecord($course);

    $this->actingAs($mentor)
        ->post(route('cpt.join-local', $cpt))
        ->assertRedirect();

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'local_id' => $mentor->id]);
});

test('joining as local confirms the cpt when an examiner is already assigned', function () {
    $course       = cptHttpTwrCourse();
    $examinerUser = User::factory()->create();
    $cpt          = cptHttpRecord($course, ['examiner_id' => $examinerUser->id]);
    $mentor       = cptHttpMentor($course);

    $this->actingAs($mentor)->post(route('cpt.join-local', $cpt));

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'confirmed' => true]);
});

test('non-course-mentor superuser cannot join as local contact', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $super  = cptHttpSuperuser(); // not a course mentor

    $this->actingAs($super)
        ->post(route('cpt.join-local', $cpt))
        ->assertSessionHasErrors('error');
});

test('cannot join as local contact when already the local contact', function () {
    $course = cptHttpTwrCourse();
    $mentor = cptHttpMentor($course);
    $cpt    = cptHttpRecord($course, ['local_id' => $mentor->id]);

    $this->actingAs($mentor)
        ->post(route('cpt.join-local', $cpt))
        ->assertSessionHasErrors('error');
});

test('cannot join as local contact when already the examiner', function () {
    $course = cptHttpTwrCourse();
    $mentor = cptHttpMentor($course);
    $cpt    = cptHttpRecord($course, ['examiner_id' => $mentor->id]);

    $this->actingAs($mentor)
        ->post(route('cpt.join-local', $cpt))
        ->assertSessionHasErrors('error');
});

// ─── CptAssignmentController: leave-local ────────────────────────────────────

test('local contact can leave their assignment', function () {
    $course = cptHttpTwrCourse();
    $mentor = cptHttpMentor($course);
    $cpt    = cptHttpRecord($course, ['local_id' => $mentor->id]);

    $this->actingAs($mentor)
        ->post(route('cpt.leave-local', $cpt))
        ->assertRedirect();

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'local_id' => null]);
});

test('non-local user cannot leave as local contact', function () {
    $course    = cptHttpTwrCourse();
    $cpt       = cptHttpRecord($course);
    $otherUser = cptHttpSuperuser();

    $this->actingAs($otherUser)
        ->post(route('cpt.leave-local', $cpt))
        ->assertSessionHasErrors('error');
});

// ─── CptGradingController ─────────────────────────────────────────────────────

test('unauthenticated user cannot grade a cpt', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $this->post(route('cpt.grade', ['cpt' => $cpt, 'result' => 1]))->assertRedirect();
});

test('non-mentor cannot grade a cpt', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $this->actingAs(User::factory()->create())
        ->post(route('cpt.grade', ['cpt' => $cpt, 'result' => 1]))
        ->assertForbidden();
});

test('non-superuser mentor cannot grade a cpt', function () {
    $course = cptHttpTwrCourse();
    $mentor = cptHttpMentor($course);
    $cpt    = cptHttpRecord($course);

    $this->actingAs($mentor)
        ->post(route('cpt.grade', ['cpt' => $cpt, 'result' => 1]))
        ->assertSessionHasErrors('error');

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'passed' => null]);
});

test('superuser can grade a cpt as passed', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);

    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.grade', ['cpt' => $cpt, 'result' => 1]));

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'passed' => true]);
});

test('superuser can grade a cpt as failed', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);

    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.grade', ['cpt' => $cpt, 'result' => 0]));

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'passed' => false]);
});

test('invalid grade result value returns an error', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);

    $this->actingAs(cptHttpSuperuser())
        ->post(route('cpt.grade', ['cpt' => $cpt, 'result' => 2]))
        ->assertSessionHasErrors('error');

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'passed' => null]);
});

// ─── CptLogController: upload page ───────────────────────────────────────────

test('unauthenticated user cannot view the log upload page', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $this->get(route('cpt.upload', $cpt))->assertRedirect();
});

test('course mentor who is not examiner or local is redirected from upload page', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course); // no examiner/local assigned
    $mentor = cptHttpMentor($course); // course mentor but not examiner/local for this cpt

    $this->actingAs($mentor)
        ->get(route('cpt.upload', $cpt))
        ->assertRedirect(route('cpt.index'));
});

test('superuser can access the log upload page', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);

    $this->actingAs(cptHttpSuperuser())
        ->get(route('cpt.upload', $cpt))
        ->assertOk();
});

test('assigned examiner can access the log upload page', function () {
    $course       = cptHttpTwrCourse();
    $examinerUser = cptHttpSuperuser();
    $cpt          = cptHttpRecord($course, ['examiner_id' => $examinerUser->id]);

    $this->actingAs($examinerUser)
        ->get(route('cpt.upload', $cpt))
        ->assertOk();
});

test('assigned local contact can access the log upload page', function () {
    $course    = cptHttpTwrCourse();
    $localUser = cptHttpSuperuser();
    $cpt       = cptHttpRecord($course, ['local_id' => $localUser->id]);

    $this->actingAs($localUser)
        ->get(route('cpt.upload', $cpt))
        ->assertOk();
});

// ─── CptLogController: upload ─────────────────────────────────────────────────

test('unauthenticated user cannot upload a log', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $this->post(route('cpt.upload.store', $cpt), [])->assertRedirect();
});

test('non-mentor cannot upload a log', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $this->actingAs(User::factory()->create())
        ->post(route('cpt.upload.store', $cpt), [])
        ->assertForbidden();
});

test('upload fails validation when no file is provided', function () {
    $course       = cptHttpTwrCourse();
    $examinerUser = cptHttpSuperuser();
    $cpt          = cptHttpRecord($course, ['examiner_id' => $examinerUser->id]);

    $this->actingAs($examinerUser)
        ->post(route('cpt.upload.store', $cpt), [])
        ->assertSessionHasErrors('log_file');
});

test('upload fails validation when file is not a pdf', function () {
    $course       = cptHttpTwrCourse();
    $examinerUser = cptHttpSuperuser();
    $cpt          = cptHttpRecord($course, ['examiner_id' => $examinerUser->id]);
    $file         = UploadedFile::fake()->create('log.txt', 5);

    $this->actingAs($examinerUser)
        ->post(route('cpt.upload.store', $cpt), ['log_file' => $file])
        ->assertSessionHasErrors('log_file');
});

test('examiner can upload a log for their cpt', function () {
    $course       = cptHttpTwrCourse();
    $examinerUser = cptHttpSuperuser();
    $cpt          = cptHttpRecord($course, ['examiner_id' => $examinerUser->id]);
    $file         = UploadedFile::fake()->create('log.pdf', 100, 'application/pdf');

    $this->actingAs($examinerUser)
        ->post(route('cpt.upload.store', $cpt), ['log_file' => $file])
        ->assertRedirect();

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'log_uploaded' => true]);
    $this->assertDatabaseCount('cpt_logs', 1);
});

test('local contact can upload a log for their cpt', function () {
    $course    = cptHttpTwrCourse();
    $localUser = cptHttpSuperuser();
    $cpt       = cptHttpRecord($course, ['local_id' => $localUser->id]);
    $file      = UploadedFile::fake()->create('log.pdf', 100, 'application/pdf');

    $this->actingAs($localUser)
        ->post(route('cpt.upload.store', $cpt), ['log_file' => $file])
        ->assertRedirect();

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'log_uploaded' => true]);
});

test('superuser can upload a log for any cpt', function () {
    $course    = cptHttpTwrCourse();
    $cpt       = cptHttpRecord($course);
    $superuser = cptHttpSuperuser();
    $file      = UploadedFile::fake()->create('log.pdf', 100, 'application/pdf');

    $this->actingAs($superuser)
        ->post(route('cpt.upload.store', $cpt), ['log_file' => $file])
        ->assertRedirect();

    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'log_uploaded' => true]);
});

test('course mentor not assigned as examiner or local cannot upload a log', function () {
    $course = cptHttpTwrCourse();
    $cpt    = cptHttpRecord($course);
    $mentor = cptHttpMentor($course); // course mentor but not examiner/local
    $file   = UploadedFile::fake()->create('log.pdf', 100, 'application/pdf');

    $this->actingAs($mentor)
        ->post(route('cpt.upload.store', $cpt), ['log_file' => $file])
        ->assertSessionHasErrors('error');
});
