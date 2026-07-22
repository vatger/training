<?php

use App\Models\Course;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TrainingLog;
use App\Models\User;
use App\Models\WaitingListRestriction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

function createLog(array $overrides = []): TrainingLog
{
    $trainee = isset($overrides['trainee_id'])
        ? User::find($overrides['trainee_id'])
        : User::factory()->create();
    $mentor = isset($overrides['mentor_id'])
        ? User::find($overrides['mentor_id'])
        : User::factory()->create();
    $course = isset($overrides['course_id'])
        ? Course::find($overrides['course_id'])
        : Course::factory()->create();

    return TrainingLog::create(array_merge([
        'trainee_id' => $trainee->id,
        'mentor_id' => $mentor->id,
        'course_id' => $course->id,
        'session_date' => now(),
        'position' => 'EDDL_TWR',
        'type' => 'O',
        'theory' => 0,
        'phraseology' => 0,
        'coordination' => 0,
        'tag_management' => 0,
        'situational_awareness' => 0,
        'problem_recognition' => 0,
        'traffic_planning' => 0,
        'reaction' => 0,
        'separation' => 0,
        'efficiency' => 0,
        'ability_to_work_under_pressure' => 0,
        'motivation' => 0,
        'result' => false,
    ], $overrides));
}

// ─── Attributes ───────────────────────────────────────────────────────────────

test('full_name concatenates first and last name', function () {
    $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

    expect($user->full_name)->toBe('John Doe');
    expect($user->name)->toBe('John Doe');
});

// ─── isMentor ─────────────────────────────────────────────────────────────────

test('isMentor returns true for EDGG Mentor role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'EDGG Mentor']);
    $user->roles()->attach($role->id);

    expect($user->isMentor())->toBeTrue();
});

test('isMentor returns true for EDMM Mentor role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'EDMM Mentor']);
    $user->roles()->attach($role->id);

    expect($user->isMentor())->toBeTrue();
});

test('isMentor returns true for EDWW Mentor role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'EDWW Mentor']);
    $user->roles()->attach($role->id);

    expect($user->isMentor())->toBeTrue();
});

test('isMentor returns true for ATD Leitung role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'ATD Leitung']);
    $user->roles()->attach($role->id);

    expect($user->isMentor())->toBeTrue();
});

test('isMentor returns false when user has no mentor roles', function () {
    $user = User::factory()->create();

    expect($user->isMentor())->toBeFalse();
});

test('isMentor returns false for unrelated role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'Some Other Role']);
    $user->roles()->attach($role->id);

    expect($user->isMentor())->toBeFalse();
});

// ─── isSuperuser ──────────────────────────────────────────────────────────────

test('isSuperuser returns true when is_admin is true', function () {
    $user = User::factory()->create(['is_admin' => true, 'is_superuser' => false]);

    expect($user->isSuperuser())->toBeTrue();
});

test('isSuperuser returns true when is_superuser is true', function () {
    $user = User::factory()->create(['is_superuser' => true, 'is_admin' => false]);

    expect($user->isSuperuser())->toBeTrue();
});

test('isSuperuser returns false when both flags are false', function () {
    $user = User::factory()->create(['is_superuser' => false, 'is_admin' => false]);

    expect($user->isSuperuser())->toBeFalse();
});

// ─── isLeadership ─────────────────────────────────────────────────────────────

test('isLeadership returns true for ATD Leitung role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'ATD Leitung']);
    $user->roles()->attach($role->id);

    expect($user->isLeadership())->toBeTrue();
});

test('isLeadership returns true for VATGER Leitung role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'VATGER Leitung']);
    $user->roles()->attach($role->id);

    expect($user->isLeadership())->toBeTrue();
});

test('isLeadership returns false for mentor role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'EDGG Mentor']);
    $user->roles()->attach($role->id);

    expect($user->isLeadership())->toBeFalse();
});

test('isLeadership returns false with no roles', function () {
    $user = User::factory()->create();

    expect($user->isLeadership())->toBeFalse();
});

// ─── isAdmin ──────────────────────────────────────────────────────────────────

test('isAdmin returns true when is_admin is true', function () {
    $user = User::factory()->create(['is_admin' => true]);

    expect($user->isAdmin())->toBeTrue();
});

test('isAdmin returns false when is_admin is false', function () {
    $user = User::factory()->create(['is_admin' => false]);

    expect($user->isAdmin())->toBeFalse();
});

// ─── isVatsimUser ─────────────────────────────────────────────────────────────

test('isVatsimUser returns true when vatsim_id is set', function () {
    $user = User::factory()->create(['vatsim_id' => 1234567]);

    expect($user->isVatsimUser())->toBeTrue();
});

test('isVatsimUser returns false when vatsim_id is null', function () {
    // vatsim_id is NOT NULL in the DB, so we test via make() without persisting.
    $user = User::factory()->make(['vatsim_id' => null]);

    expect($user->isVatsimUser())->toBeFalse();
});

// ─── hasPermission (direct) ───────────────────────────────────────────────────

test('hasPermission returns true for a directly attached permission', function () {
    $user = User::factory()->create();
    $permission = Permission::create(['name' => 'admin.users.view']);
    $user->permissions()->attach($permission->id);

    expect($user->hasPermission('admin.users.view'))->toBeTrue();
});

test('hasPermission returns false for a permission the user does not have', function () {
    $user = User::factory()->create();
    $permission = Permission::create(['name' => 'admin.users.view']);
    $user->permissions()->attach($permission->id);

    expect($user->hasPermission('admin.users.edit'))->toBeFalse();
});

// ─── hasPermission (via role) ─────────────────────────────────────────────────

test('hasPermission returns true when permission is inherited through a role', function () {
    $user = User::factory()->create();
    $permission = Permission::create(['name' => 'admin.users.view']);
    $role = Role::create(['name' => 'Test Role']);
    $role->permissions()->attach($permission->id);
    $user->roles()->attach($role->id);

    $freshUser = User::find($user->id);

    expect($freshUser->hasPermission('admin.users.view'))->toBeTrue();
});

// ─── canViewCourse ────────────────────────────────────────────────────────────

test('canViewCourse returns true for an admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $course = Course::factory()->create();

    expect($admin->canViewCourse($course))->toBeTrue();
});

test('canViewCourse returns true when user is a mentor of the course', function () {
    $mentor = User::factory()->create();
    $course = Course::factory()->create();
    $course->mentors()->attach($mentor->id);

    expect($mentor->canViewCourse($course))->toBeTrue();
});

test('canViewCourse returns true when user is chief of training for the course', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    DB::table('chief_of_trainings')->insert(['user_id' => $user->id, 'course_id' => $course->id]);

    expect($user->canViewCourse($course))->toBeTrue();
});

test('canViewCourse returns false for an unrelated user', function () {
    $other = User::factory()->create();
    $course = Course::factory()->create();

    expect($other->canViewCourse($course))->toBeFalse();
});

// ─── canEditTrainingLog ───────────────────────────────────────────────────────

test('canEditTrainingLog returns true for an admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $course = Course::factory()->create();

    $log = createLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id]);

    expect($admin->canEditTrainingLog($log))->toBeTrue();
});

test('canEditTrainingLog returns true for the log creator', function () {
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $course = Course::factory()->create();

    $log = createLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id]);

    expect($mentor->canEditTrainingLog($log))->toBeTrue();
});

test('canEditTrainingLog returns true for chief of training of the course', function () {
    $cot = User::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $course = Course::factory()->create();

    DB::table('chief_of_trainings')->insert(['user_id' => $cot->id, 'course_id' => $course->id]);

    $log = createLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id]);

    expect($cot->canEditTrainingLog($log))->toBeTrue();
});

test('canEditTrainingLog returns false for an unrelated user', function () {
    $other = User::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $course = Course::factory()->create();

    $log = createLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id]);

    expect($other->canEditTrainingLog($log))->toBeFalse();
});

// ─── Scopes ───────────────────────────────────────────────────────────────────

test('mentors scope returns only users with mentor roles', function () {
    $mentor = User::factory()->create();
    $other = User::factory()->create();
    $role = Role::create(['name' => 'EDGG Mentor']);
    $mentor->roles()->attach($role->id);

    $results = User::mentors()->get();

    expect($results->contains($mentor))->toBeTrue();
    expect($results->contains($other))->toBeFalse();
});

test('leadership scope returns only users with leadership roles', function () {
    $leader = User::factory()->create();
    $other = User::factory()->create();
    $role = Role::create(['name' => 'ATD Leitung']);
    $leader->roles()->attach($role->id);

    $results = User::leadership()->get();

    expect($results->contains($leader))->toBeTrue();
    expect($results->contains($other))->toBeFalse();
});

test('admins scope returns only admin users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $other = User::factory()->create(['is_admin' => false]);

    $results = User::admins()->get();

    expect($results->contains($admin))->toBeTrue();
    expect($results->contains($other))->toBeFalse();
});

// ─── isRestrictedFrom ─────────────────────────────────────────────────────────

test('isRestrictedFrom returns false when no restriction exists', function () {
    $user = User::factory()->create();

    expect($user->isRestrictedFrom('RTG'))->toBeFalse();
});

test('isRestrictedFrom returns true for an active non-expiring restriction', function () {
    $user = User::factory()->create();
    WaitingListRestriction::create(['user_id' => $user->id, 'type' => 'RTG', 'expires_at' => null]);

    expect($user->isRestrictedFrom('RTG'))->toBeTrue();
});

test('isRestrictedFrom returns false for an expired restriction', function () {
    $user = User::factory()->create();
    WaitingListRestriction::create([
        'user_id' => $user->id,
        'type' => 'EDMT',
        'expires_at' => now()->subDay(),
    ]);

    expect($user->isRestrictedFrom('EDMT'))->toBeFalse();
});

test('isRestrictedFrom returns true for a restriction that has not yet expired', function () {
    $user = User::factory()->create();
    WaitingListRestriction::create([
        'user_id' => $user->id,
        'type' => 'GST',
        'expires_at' => now()->addDay(),
    ]);

    expect($user->isRestrictedFrom('GST'))->toBeTrue();
});

// ─── isLeadingMentor / isLeadingMentorForFir ─────────────────────────────────

test('isLeadingMentor returns false when user has no leading mentor records', function () {
    $user = User::factory()->create();

    expect($user->isLeadingMentor())->toBeFalse();
});

test('isLeadingMentor returns true when user has a leading mentor record', function () {
    $user = User::factory()->create();
    \App\Models\LeadingMentor::create(['user_id' => $user->id, 'fir' => 'EDGG']);

    expect($user->isLeadingMentor())->toBeTrue();
});

test('isLeadingMentorForFir returns true for the assigned FIR', function () {
    $user = User::factory()->create();
    \App\Models\LeadingMentor::create(['user_id' => $user->id, 'fir' => 'EDMM']);

    expect($user->isLeadingMentorForFir('EDMM'))->toBeTrue();
});

test('isLeadingMentorForFir returns false for a different FIR', function () {
    $user = User::factory()->create();
    \App\Models\LeadingMentor::create(['user_id' => $user->id, 'fir' => 'EDMM']);

    expect($user->isLeadingMentorForFir('EDGG'))->toBeFalse();
});

test('isLeadingMentorForFir returns false when user has no leading mentor records', function () {
    $user = User::factory()->create();

    expect($user->isLeadingMentorForFir('EDGG'))->toBeFalse();
});

test('a leading mentor can be assigned to multiple FIRs', function () {
    $user = User::factory()->create();
    \App\Models\LeadingMentor::create(['user_id' => $user->id, 'fir' => 'EDGG']);
    \App\Models\LeadingMentor::create(['user_id' => $user->id, 'fir' => 'EDWW']);

    expect($user->isLeadingMentorForFir('EDGG'))->toBeTrue();
    expect($user->isLeadingMentorForFir('EDWW'))->toBeTrue();
    expect($user->isLeadingMentorForFir('EDMM'))->toBeFalse();
});

// ─── canViewCourse — leading mentor ──────────────────────────────────────────

test('canViewCourse returns true for a leading mentor of the course FIR', function () {
    $lm = User::factory()->create();
    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['mentor_group_id' => $role->id]);
    \App\Models\LeadingMentor::create(['user_id' => $lm->id, 'fir' => 'EDGG']);

    // Fresh load to clear the internal FIR cache.
    $lm = User::find($lm->id);

    expect($lm->canViewCourse($course))->toBeTrue();
});

test('canViewCourse returns false for a leading mentor of a different FIR', function () {
    $lm = User::factory()->create();
    $role = Role::create(['name' => 'EDMM Mentor']);
    $course = Course::factory()->create(['mentor_group_id' => $role->id]);
    \App\Models\LeadingMentor::create(['user_id' => $lm->id, 'fir' => 'EDGG']);

    $lm = User::find($lm->id);

    expect($lm->canViewCourse($course))->toBeFalse();
});

test('canViewCourse returns false for a leading mentor when the course has no mentor group', function () {
    $lm = User::factory()->create();
    $course = Course::factory()->create(['mentor_group_id' => null]);
    \App\Models\LeadingMentor::create(['user_id' => $lm->id, 'fir' => 'EDGG']);

    $lm = User::find($lm->id);

    expect($lm->canViewCourse($course))->toBeFalse();
});

// ─── canEditTrainingLog — leading mentor ──────────────────────────────────────

test('canEditTrainingLog returns true for a leading mentor of the course FIR', function () {
    $lm = User::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['mentor_group_id' => $role->id]);
    \App\Models\LeadingMentor::create(['user_id' => $lm->id, 'fir' => 'EDGG']);

    $log = createLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id]);
    $lm = User::find($lm->id);

    expect($lm->canEditTrainingLog($log))->toBeTrue();
});

test('canEditTrainingLog returns false for a leading mentor of a different FIR', function () {
    $lm = User::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $role = Role::create(['name' => 'EDMM Mentor']);
    $course = Course::factory()->create(['mentor_group_id' => $role->id]);
    \App\Models\LeadingMentor::create(['user_id' => $lm->id, 'fir' => 'EDGG']);

    $log = createLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id]);
    $lm = User::find($lm->id);

    expect($lm->canEditTrainingLog($log))->toBeFalse();
});

test('canEditTrainingLog returns false for a leading mentor when course has no mentor group', function () {
    $lm = User::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $course = Course::factory()->create(['mentor_group_id' => null]);
    \App\Models\LeadingMentor::create(['user_id' => $lm->id, 'fir' => 'EDGG']);

    $log = createLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id]);
    $lm = User::find($lm->id);

    expect($lm->canEditTrainingLog($log))->toBeFalse();
});
