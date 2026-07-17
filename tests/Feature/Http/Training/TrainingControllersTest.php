<?php

use App\Integrations\Moodle\FakeMoodleClient;
use App\Integrations\Moodle\MoodleClientInterface;
use App\Integrations\VatEud\DTOs\ExamResultData;
use App\Integrations\VatEud\DTOs\UserExamsData;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Integrations\Vatger\FakeVatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\Course;
use App\Models\EndorsementActivity;
use App\Models\Role;
use App\Models\Tier2Endorsement;
use App\Models\TrainingLog;
use App\Models\User;
use App\Models\WaitingListEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->app->bind(VatEudClientInterface::class, FakeVatEudClient::class);
    $this->app->bind(MoodleClientInterface::class, FakeMoodleClient::class);
    $this->app->bind(VatgerClientInterface::class, FakeVatgerClient::class);
    Http::fake(['*' => Http::response(['data' => ['controllers' => []]], 200)]);
    Cache::flush();

    Event::fake();
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function trainingHttpSuperuser(): User
{
    return User::factory()->superuser()->create();
}

function trainingHttpMentor(Course $course): User
{
    $role = Role::firstOrCreate(['name' => 'EDGG Mentor'], ['description' => 'Mentor']);
    $user = User::factory()->create();
    $user->roles()->attach($role);
    $course->mentors()->attach($user->id);
    return $user;
}

function trainingHttpMentorNoAccess(): User
{
    $role = Role::firstOrCreate(['name' => 'EDGG Mentor'], ['description' => 'Mentor']);
    $user = User::factory()->create();
    $user->roles()->attach($role);
    return $user;
}

function trainingHttpRtgCourse(): Course
{
    return Course::factory()->create([
        'type'         => 'RTG',
        'position'     => 'TWR',
        'solo_station' => 'EDDF_TWR',
        'airport_icao' => 'EDDF',
    ]);
}

function trainingHttpAttachTrainee(Course $course, User $trainee, array $attrs = []): void
{
    DB::table('course_trainees')->insert(array_merge([
        'course_id'            => $course->id,
        'user_id'              => $trainee->id,
        'claimed_by_mentor_id' => null,
        'claimed_at'           => null,
        'completed_at'         => null,
        'remarks'              => null,
        'remark_author_id'     => null,
        'remark_updated_at'    => null,
        'custom_order'         => null,
        'status'               => 'active',
        'created_at'           => now(),
        'updated_at'           => now(),
    ], $attrs));
}

function trainingHttpAttachCompletedTrainee(Course $course, User $trainee): void
{
    trainingHttpAttachTrainee($course, $trainee, ['completed_at' => now()->subDay()]);
}

function trainingHttpLogData(User $trainee, Course $course): array
{
    return [
        'trainee_id'                      => $trainee->id,
        'course_id'                       => $course->id,
        'session_date'                    => now()->subDay()->format('Y-m-d'),
        'position'                        => 'EDDF_TWR',
        'type'                            => 'O',
        'theory'                          => 3,
        'phraseology'                     => 3,
        'coordination'                    => 3,
        'tag_management'                  => 3,
        'situational_awareness'           => 3,
        'problem_recognition'             => 3,
        'traffic_planning'                => 3,
        'reaction'                        => 3,
        'separation'                      => 3,
        'efficiency'                      => 3,
        'ability_to_work_under_pressure'  => 3,
        'motivation'                      => 3,
        'result'                          => true,
    ];
}

function trainingHttpCreateLog(User $mentor, User $trainee, Course $course): TrainingLog
{
    return TrainingLog::create(array_merge(
        trainingHttpLogData($trainee, $course),
        ['mentor_id' => $mentor->id],
    ));
}

// ─── TraineeManagementController: addTraineeToCourse ──────────────────────────

test('unauthenticated user is redirected when adding trainee', function () {
    $this->post(route('overview.add-trainee-to-course'), [])->assertRedirect();
});

test('non-mentor cannot add trainee to course', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.add-trainee-to-course'), [])
        ->assertForbidden();
});

test('mentor without course access cannot add trainee', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    $mentor  = trainingHttpMentorNoAccess();

    $this->actingAs($mentor)
        ->post(route('overview.add-trainee-to-course'), [
            'course_id' => $course->id,
            'user_id'   => $trainee->id,
        ])
        ->assertSessionHasErrors('error');
});

test('mentor can add a trainee to their course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->post(route('overview.add-trainee-to-course'), [
            'course_id' => $course->id,
            'user_id'   => $trainee->id,
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));

    $this->assertDatabaseHas('course_trainees', [
        'course_id' => $course->id,
        'user_id'   => $trainee->id,
    ]);
});

test('superuser can add trainee to any course', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();

    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.add-trainee-to-course'), [
            'course_id' => $course->id,
            'user_id'   => $trainee->id,
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));
});

test('cannot add trainee who is already active in course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);

    $this->actingAs($mentor)
        ->post(route('overview.add-trainee-to-course'), [
            'course_id' => $course->id,
            'user_id'   => $trainee->id,
        ])
        ->assertSessionHasErrors('error');
});

test('add trainee fails validation when required fields are missing', function () {
    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.add-trainee-to-course'), [])
        ->assertSessionHasErrors(['course_id', 'user_id']);
});

// ─── TraineeManagementController: removeTrainee ───────────────────────────────

test('unauthenticated user is redirected when removing trainee', function () {
    $this->post(route('overview.remove-trainee'), [])->assertRedirect();
});

test('non-mentor cannot remove trainee', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.remove-trainee'), [])
        ->assertForbidden();
});

test('mentor without course access cannot remove trainee', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $mentor = trainingHttpMentorNoAccess();

    $this->actingAs($mentor)
        ->post(route('overview.remove-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertSessionHasErrors('error');
});

test('mentor can remove trainee from their course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);

    $this->actingAs($mentor)
        ->post(route('overview.remove-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));
});

test('superuser can remove trainee from any course', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);

    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.remove-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertRedirect();
});

// ─── TraineeManagementController: claimTrainee ────────────────────────────────

test('unauthenticated user is redirected when claiming trainee', function () {
    $this->post(route('overview.claim-trainee'), [])->assertRedirect();
});

test('non-mentor cannot claim a trainee', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.claim-trainee'), [])
        ->assertForbidden();
});

test('mentor without course access cannot claim a trainee', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $mentor = trainingHttpMentorNoAccess();

    $this->actingAs($mentor)
        ->post(route('overview.claim-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertSessionHasErrors('error');
});

test('mentor cannot claim trainee not in their course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->post(route('overview.claim-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertSessionHasErrors('error');
});

test('mentor can claim a trainee in their course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);

    $this->actingAs($mentor)
        ->post(route('overview.claim-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));

    $this->assertDatabaseHas('course_trainees', [
        'course_id'            => $course->id,
        'user_id'              => $trainee->id,
        'claimed_by_mentor_id' => $mentor->id,
    ]);
});

test('claim trainee fails validation when required fields are missing', function () {
    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.claim-trainee'), [])
        ->assertSessionHasErrors(['trainee_id', 'course_id']);
});

// ─── TraineeManagementController: unclaimTrainee ─────────────────────────────

test('unauthenticated user is redirected when unclaiming trainee', function () {
    $this->post(route('overview.unclaim-trainee'), [])->assertRedirect();
});

test('non-mentor cannot unclaim a trainee', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.unclaim-trainee'), [])
        ->assertForbidden();
});

test('mentor without course access cannot unclaim a trainee', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $mentor = trainingHttpMentorNoAccess();

    $this->actingAs($mentor)
        ->post(route('overview.unclaim-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertSessionHasErrors('error');
});

test('mentor can unclaim a trainee in their course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee, ['claimed_by_mentor_id' => $mentor->id]);

    $this->actingAs($mentor)
        ->post(route('overview.unclaim-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));

    $this->assertDatabaseHas('course_trainees', [
        'course_id'            => $course->id,
        'user_id'              => $trainee->id,
        'claimed_by_mentor_id' => null,
    ]);
});

// ─── TraineeManagementController: assignTrainee ───────────────────────────────

test('unauthenticated user is redirected when assigning trainee', function () {
    $this->post(route('overview.assign-trainee'), [])->assertRedirect();
});

test('non-mentor cannot assign a trainee', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.assign-trainee'), [])
        ->assertForbidden();
});

test('mentor can assign a trainee to another mentor in the same course', function () {
    $course     = trainingHttpRtgCourse();
    $mentor     = trainingHttpMentor($course);
    $newMentor  = trainingHttpMentor($course);
    $trainee    = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);

    $this->actingAs($mentor)
        ->post(route('overview.assign-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
            'mentor_id'  => $newMentor->id,
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));

    $this->assertDatabaseHas('course_trainees', [
        'course_id'            => $course->id,
        'user_id'              => $trainee->id,
        'claimed_by_mentor_id' => $newMentor->id,
    ]);
});

test('cannot assign trainee to a user who is not a mentor for that course', function () {
    $course      = trainingHttpRtgCourse();
    $mentor      = trainingHttpMentor($course);
    $trainee     = User::factory()->create();
    $nonMentor   = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);

    $this->actingAs($mentor)
        ->post(route('overview.assign-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
            'mentor_id'  => $nonMentor->id,
        ])
        ->assertSessionHasErrors('error');
});

test('assign trainee fails validation when required fields are missing', function () {
    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.assign-trainee'), [])
        ->assertSessionHasErrors(['trainee_id', 'course_id', 'mentor_id']);
});

// ─── TraineeManagementController: reactivateTrainee ──────────────────────────

test('unauthenticated user is redirected when reactivating trainee', function () {
    $this->post(route('overview.reactivate-trainee'), [])->assertRedirect();
});

test('non-mentor cannot reactivate a trainee', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.reactivate-trainee'), [])
        ->assertForbidden();
});

test('mentor can reactivate a completed trainee in their course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachCompletedTrainee($course, $trainee);

    $this->actingAs($mentor)
        ->post(route('overview.reactivate-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));

    $this->assertDatabaseHas('course_trainees', [
        'course_id'    => $course->id,
        'user_id'      => $trainee->id,
        'completed_at' => null,
    ]);
});

test('cannot reactivate a trainee who has not completed the course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);

    $this->actingAs($mentor)
        ->post(route('overview.reactivate-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertSessionHasErrors('error');
});

// ─── TraineeManagementController: finishCourse ────────────────────────────────

test('unauthenticated user is redirected when finishing a course', function () {
    $this->post(route('overview.finish-trainee'), [])->assertRedirect();
});

test('non-mentor cannot finish a trainee course', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.finish-trainee'), [])
        ->assertForbidden();
});

test('mentor can finish an active trainee in their course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);

    $this->actingAs($mentor)
        ->post(route('overview.finish-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));

    $this->assertDatabaseMissing('course_trainees', [
        'course_id'    => $course->id,
        'user_id'      => $trainee->id,
        'completed_at' => null,
    ]);
});

test('cannot finish a trainee who is not in the course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->post(route('overview.finish-trainee'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertSessionHasErrors('error');
});

// ─── TrainingRemarkController: updateRemark ───────────────────────────────────

test('unauthenticated user is redirected when updating remark', function () {
    $this->post(route('overview.update-remark'), [])->assertRedirect();
});

test('non-mentor cannot update a remark', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.update-remark'), [])
        ->assertForbidden();
});

test('mentor without course access cannot update a remark', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $mentor = trainingHttpMentorNoAccess();

    $this->actingAs($mentor)
        ->post(route('overview.update-remark'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
            'remark'     => 'Good progress.',
        ])
        ->assertSessionHasErrors('error');
});

test('mentor can update a trainee remark in their course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);

    $this->actingAs($mentor)
        ->post(route('overview.update-remark'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
            'remark'     => 'Good progress.',
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));

    $this->assertDatabaseHas('course_trainees', [
        'course_id' => $course->id,
        'user_id'   => $trainee->id,
        'remarks'   => 'Good progress.',
    ]);
});

test('remark update fails validation when trainee or course are missing', function () {
    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.update-remark'), [])
        ->assertSessionHasErrors(['trainee_id', 'course_id']);
});

test('remark can be set to null', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee, ['remarks' => 'Old remark']);

    $this->actingAs($mentor)
        ->post(route('overview.update-remark'), [
            'course_id'  => $course->id,
            'trainee_id' => $trainee->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('course_trainees', [
        'course_id' => $course->id,
        'user_id'   => $trainee->id,
        'remarks'   => '',
    ]);
});

// ─── MentorManagementController: addMentor ────────────────────────────────────

test('unauthenticated user is redirected when adding mentor', function () {
    $this->post(route('overview.add-mentor'), [])->assertRedirect();
});

test('non-mentor cannot add a mentor', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.add-mentor'), [])
        ->assertForbidden();
});

test('mentor without course access cannot add a mentor', function () {
    $course       = trainingHttpRtgCourse();
    $mentor       = trainingHttpMentorNoAccess();
    $mentorToAdd  = trainingHttpMentor($course);

    $this->actingAs($mentor)
        ->post(route('overview.add-mentor'), [
            'course_id' => $course->id,
            'user_id'   => $mentorToAdd->id,
        ])
        ->assertSessionHasErrors('error');
});

test('mentor can add another mentor to their course', function () {
    $course      = trainingHttpRtgCourse();
    $mentor      = trainingHttpMentor($course);
    $newMentor   = trainingHttpMentorNoAccess();

    $this->actingAs($mentor)
        ->post(route('overview.add-mentor'), [
            'course_id' => $course->id,
            'user_id'   => $newMentor->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('course_mentors', [
        'course_id' => $course->id,
        'user_id'   => $newMentor->id,
    ]);
});

test('cannot add a user without mentor privileges as a course mentor', function () {
    $course     = trainingHttpRtgCourse();
    $mentor     = trainingHttpMentor($course);
    $plainUser  = User::factory()->create();

    $this->actingAs($mentor)
        ->post(route('overview.add-mentor'), [
            'course_id' => $course->id,
            'user_id'   => $plainUser->id,
        ])
        ->assertSessionHasErrors('error');
});

test('cannot add a mentor who is already a mentor for the course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);

    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.add-mentor'), [
            'course_id' => $course->id,
            'user_id'   => $mentor->id,
        ])
        ->assertSessionHasErrors('error');
});

test('add mentor fails validation when required fields are missing', function () {
    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.add-mentor'), [])
        ->assertSessionHasErrors(['course_id', 'user_id']);
});

// ─── MentorManagementController: removeMentor ─────────────────────────────────

test('unauthenticated user is redirected when removing mentor', function () {
    $this->post(route('overview.remove-mentor'), [])->assertRedirect();
});

test('non-mentor cannot remove a mentor', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.remove-mentor'), [])
        ->assertForbidden();
});

test('mentor without course access cannot remove a mentor', function () {
    $course        = trainingHttpRtgCourse();
    $mentor        = trainingHttpMentor($course);
    $outsideMentor = trainingHttpMentorNoAccess();

    $this->actingAs($outsideMentor)
        ->post(route('overview.remove-mentor'), [
            'course_id' => $course->id,
            'mentor_id' => $mentor->id,
        ])
        ->assertSessionHasErrors('error');
});

test('mentor can remove another mentor from their course', function () {
    $course        = trainingHttpRtgCourse();
    $mentor        = trainingHttpMentor($course);
    $mentorToRemove = trainingHttpMentor($course);

    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.remove-mentor'), [
            'course_id' => $course->id,
            'mentor_id' => $mentorToRemove->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseMissing('course_mentors', [
        'course_id' => $course->id,
        'user_id'   => $mentorToRemove->id,
    ]);
});

test('cannot remove the last mentor from a course', function () {
    $course = trainingHttpRtgCourse();
    $mentor = trainingHttpMentor($course);

    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.remove-mentor'), [
            'course_id' => $course->id,
            'mentor_id' => $mentor->id,
        ])
        ->assertSessionHasErrors('error');

    $this->assertDatabaseHas('course_mentors', [
        'course_id' => $course->id,
        'user_id'   => $mentor->id,
    ]);
});

test('cannot remove a user who is not a mentor for the course', function () {
    $course   = trainingHttpRtgCourse();
    $mentor   = trainingHttpMentor($course);
    $notMentor = User::factory()->create();

    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.remove-mentor'), [
            'course_id' => $course->id,
            'mentor_id' => $notMentor->id,
        ])
        ->assertSessionHasErrors('error');
});

// ─── WaitingListController: startTraining ────────────────────────────────────

test('unauthenticated user is redirected from start-training', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    $entry   = WaitingListEntry::create([
        'course_id'  => $course->id,
        'user_id'    => $trainee->id,
        'date_added' => now(),
        'activity'   => 0,
    ]);
    $this->post(route('waiting-lists.start-training', $entry))->assertRedirect();
});

test('non-mentor cannot start training', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    $entry   = WaitingListEntry::create([
        'course_id'  => $course->id,
        'user_id'    => $trainee->id,
        'date_added' => now(),
        'activity'   => 0,
    ]);
    $this->actingAs(User::factory()->create())
        ->post(route('waiting-lists.start-training', $entry))
        ->assertForbidden();
});

test('mentor without access to the course cannot start training', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    $entry   = WaitingListEntry::create([
        'course_id'  => $course->id,
        'user_id'    => $trainee->id,
        'date_added' => now(),
        'activity'   => 0,
    ]);
    $outsideMentor = trainingHttpMentorNoAccess();

    $response = $this->actingAs($outsideMentor)
        ->post(route('waiting-lists.start-training', $entry));

    $response->assertStatus(403);
});

test('course mentor can start training for a waiting list entry', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    $entry   = WaitingListEntry::create([
        'course_id'  => $course->id,
        'user_id'    => $trainee->id,
        'date_added' => now(),
        'activity'   => 10,
    ]);

    $this->actingAs($mentor)
        ->post(route('waiting-lists.start-training', $entry))
        ->assertRedirect();

    $this->assertDatabaseHas('course_trainees', [
        'course_id' => $course->id,
        'user_id'   => $trainee->id,
    ]);
});

// ─── WaitingListController: updateRemarks ─────────────────────────────────────

test('unauthenticated user is redirected when updating waiting-list remarks', function () {
    $this->post(route('waiting-lists.update-remarks'), [])->assertRedirect();
});

test('non-mentor cannot update waiting-list remarks', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('waiting-lists.update-remarks'), [])
        ->assertForbidden();
});

test('mentor can update remarks on a waiting list entry they manage', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    $entry   = WaitingListEntry::create([
        'course_id'  => $course->id,
        'user_id'    => $trainee->id,
        'date_added' => now(),
        'activity'   => 0,
    ]);

    $this->actingAs($mentor)
        ->post(route('waiting-lists.update-remarks'), [
            'entry_id' => $entry->id,
            'remarks'  => 'Very active student.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('waiting_list_entries', [
        'id'      => $entry->id,
        'remarks' => 'Very active student.',
    ]);
});

test('mentor without course access cannot update waiting list remarks', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    $entry   = WaitingListEntry::create([
        'course_id'  => $course->id,
        'user_id'    => $trainee->id,
        'date_added' => now(),
        'activity'   => 0,
    ]);
    $outsideMentor = trainingHttpMentorNoAccess();

    $this->actingAs($outsideMentor)
        ->post(route('waiting-lists.update-remarks'), [
            'entry_id' => $entry->id,
            'remarks'  => 'Changed.',
        ])
        ->assertSessionHasErrors('error');
});

test('update remarks fails validation when entry_id is missing', function () {
    $this->actingAs(trainingHttpSuperuser())
        ->post(route('waiting-lists.update-remarks'), [])
        ->assertSessionHasErrors('entry_id');
});

// ─── TrainingLogController: show ──────────────────────────────────────────────

test('unauthenticated user is redirected from training log show', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $log = trainingHttpCreateLog($mentor, $trainee, $course);

    $this->get(route('training-logs.show', $log))->assertRedirect();
});

test('trainee can view their own training log', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $log = trainingHttpCreateLog($mentor, $trainee, $course);

    $this->actingAs($trainee)
        ->get(route('training-logs.show', $log))
        ->assertOk();
});

test('course mentor can view a training log for their course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $log = trainingHttpCreateLog($mentor, $trainee, $course);

    $this->actingAs($mentor)
        ->get(route('training-logs.show', $log))
        ->assertOk();
});

test('unrelated user cannot view a training log', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $log     = trainingHttpCreateLog($mentor, $trainee, $course);
    $other   = User::factory()->create();

    $this->actingAs($other)
        ->get(route('training-logs.show', $log))
        ->assertForbidden();
});

test('superuser can view any training log', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $log = trainingHttpCreateLog($mentor, $trainee, $course);

    $this->actingAs(trainingHttpSuperuser())
        ->get(route('training-logs.show', $log))
        ->assertOk();
});

// ─── TrainingLogController: store ─────────────────────────────────────────────

test('unauthenticated user cannot store a training log', function () {
    $this->post(route('training-logs.store'), [])->assertRedirect();
});

test('non-mentor cannot store a training log', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('training-logs.store'), [])
        ->assertForbidden();
});

test('mentor can store a training log for a trainee in their course', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);

    $this->actingAs($mentor)
        ->post(route('training-logs.store'), trainingHttpLogData($trainee, $course))
        ->assertRedirect();

    $this->assertDatabaseHas('training_logs', [
        'mentor_id'  => $mentor->id,
        'trainee_id' => $trainee->id,
        'course_id'  => $course->id,
    ]);
});

test('superuser can store a training log for any course', function () {
    $super   = trainingHttpSuperuser();
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();

    $this->actingAs($super)
        ->post(route('training-logs.store'), trainingHttpLogData($trainee, $course))
        ->assertRedirect();
});

test('store training log fails validation when required fields are missing', function () {
    $this->actingAs(trainingHttpSuperuser())
        ->post(route('training-logs.store'), [])
        ->assertSessionHasErrors(['session_date', 'position', 'type', 'result']);
});

test('store training log fails validation with future session date', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    $data    = array_merge(trainingHttpLogData($trainee, $course), [
        'session_date' => now()->addDay()->format('Y-m-d'),
    ]);

    $this->actingAs(trainingHttpSuperuser())
        ->post(route('training-logs.store'), $data)
        ->assertSessionHasErrors('session_date');
});

test('store training log fails validation with invalid session type', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    $data    = array_merge(trainingHttpLogData($trainee, $course), ['type' => 'Z']);

    $this->actingAs(trainingHttpSuperuser())
        ->post(route('training-logs.store'), $data)
        ->assertSessionHasErrors('type');
});

test('mentor cannot store a log for a course they are not a mentor of', function () {
    $course        = trainingHttpRtgCourse();
    $otherCourse   = trainingHttpRtgCourse();
    $mentor        = trainingHttpMentor($otherCourse);
    $trainee       = User::factory()->create();

    $this->actingAs($mentor)
        ->post(route('training-logs.store'), trainingHttpLogData($trainee, $course))
        ->assertForbidden();
});

// ─── TrainingLogController: update ────────────────────────────────────────────

test('unauthenticated user cannot update a training log', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $log = trainingHttpCreateLog($mentor, $trainee, $course);

    $this->put(route('training-logs.update', $log), [])->assertRedirect();
});

test('non-mentor cannot update a training log', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $log = trainingHttpCreateLog($mentor, $trainee, $course);

    $this->actingAs(User::factory()->create())
        ->put(route('training-logs.update', $log), [])
        ->assertForbidden();
});

test('mentor who created the log can update it', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $log  = trainingHttpCreateLog($mentor, $trainee, $course);
    $data = trainingHttpLogData($trainee, $course);
    unset($data['trainee_id'], $data['course_id']);

    $this->actingAs($mentor)
        ->put(route('training-logs.update', $log), $data)
        ->assertRedirect(route('training-logs.show', $log->id));

    $this->assertDatabaseHas('training_logs', ['id' => $log->id, 'position' => 'EDDF_TWR']);
});

test('a different mentor cannot update a log they did not create', function () {
    $course       = trainingHttpRtgCourse();
    $mentor       = trainingHttpMentor($course);
    $otherMentor  = trainingHttpMentor($course);
    $trainee      = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $log  = trainingHttpCreateLog($mentor, $trainee, $course);
    $data = trainingHttpLogData($trainee, $course);
    unset($data['trainee_id'], $data['course_id']);

    $this->actingAs($otherMentor)
        ->put(route('training-logs.update', $log), $data)
        ->assertForbidden();
});

test('superuser can update any training log', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();
    trainingHttpAttachTrainee($course, $trainee);
    $log  = trainingHttpCreateLog($mentor, $trainee, $course);
    $data = trainingHttpLogData($trainee, $course);
    unset($data['trainee_id'], $data['course_id']);

    $this->actingAs(trainingHttpSuperuser())
        ->put(route('training-logs.update', $log), $data)
        ->assertRedirect(route('training-logs.show', $log->id));
});

// ─── SoloController: addSolo ─────────────────────────────────────────────────

test('unauthenticated user is redirected when granting solo', function () {
    $this->post(route('overview.add-solo'), [])->assertRedirect();
});

test('non-mentor cannot grant solo endorsement', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.add-solo'), [])
        ->assertForbidden();
});

test('mentor without course access cannot grant solo', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    $mentor  = trainingHttpMentorNoAccess();

    $this->actingAs($mentor)
        ->post(route('overview.add-solo'), [
            'trainee_id'  => $trainee->id,
            'course_id'   => $course->id,
            'expiry_date' => now()->addDays(14)->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('error');
});

test('solo endorsement cannot be granted for non-RTG courses', function () {
    $course  = Course::factory()->create(['type' => 'EDMT', 'solo_station' => null]);
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->post(route('overview.add-solo'), [
            'trainee_id'  => $trainee->id,
            'course_id'   => $course->id,
            'expiry_date' => now()->addDays(14)->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('error');
});

test('solo endorsement expiry cannot exceed 31 days', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->post(route('overview.add-solo'), [
            'trainee_id'  => $trainee->id,
            'course_id'   => $course->id,
            'expiry_date' => now()->addDays(32)->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('error');
});

test('add solo fails validation when required fields are missing', function () {
    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.add-solo'), [])
        ->assertSessionHasErrors(['trainee_id', 'course_id', 'expiry_date']);
});

test('add solo fails validation when expiry date is within 6 days', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();

    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.add-solo'), [
            'trainee_id'  => $trainee->id,
            'course_id'   => $course->id,
            'expiry_date' => now()->addDays(3)->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('expiry_date');
});

test('mentor can grant solo endorsement when prerequisites are met', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();

    $passingClient = new class extends FakeVatEudClient {
        public function getUserExams(int $vatsimId): UserExamsData
        {
            return new UserExamsData(
                results: [
                    new ExamResultData(examId: 9, passed: true, expiry: Carbon::now()->addYear()),
                ],
                assignments: [],
            );
        }
    };
    $this->app->instance(VatEudClientInterface::class, $passingClient);

    $this->actingAs($mentor)
        ->post(route('overview.add-solo'), [
            'trainee_id'  => $trainee->id,
            'course_id'   => $course->id,
            'expiry_date' => now()->addDays(14)->format('Y-m-d'),
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));
});

// ─── SoloController: extendSolo ───────────────────────────────────────────────

test('unauthenticated user is redirected when extending solo', function () {
    $this->post(route('overview.extend-solo'), [])->assertRedirect();
});

test('non-mentor cannot extend solo endorsement', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.extend-solo'), [])
        ->assertForbidden();
});

test('extend solo fails validation when required fields are missing', function () {
    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.extend-solo'), [])
        ->assertSessionHasErrors(['trainee_id', 'course_id', 'expiry_date']);
});

test('extend solo endorsement expiry cannot exceed 31 days', function () {
    $course  = trainingHttpRtgCourse();
    $mentor  = trainingHttpMentor($course);
    $trainee = User::factory()->create();

    $this->actingAs($mentor)
        ->post(route('overview.extend-solo'), [
            'trainee_id'  => $trainee->id,
            'course_id'   => $course->id,
            'expiry_date' => now()->addDays(32)->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('error');
});

test('mentor without course access cannot extend solo', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    $mentor  = trainingHttpMentorNoAccess();

    $this->actingAs($mentor)
        ->post(route('overview.extend-solo'), [
            'trainee_id'  => $trainee->id,
            'course_id'   => $course->id,
            'expiry_date' => now()->addDays(14)->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('error');
});

// ─── SoloController: removeSolo ───────────────────────────────────────────────

test('unauthenticated user is redirected when removing solo', function () {
    $this->post(route('overview.remove-solo'), [])->assertRedirect();
});

test('non-mentor cannot remove solo endorsement', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('overview.remove-solo'), [])
        ->assertForbidden();
});

test('remove solo fails validation when required fields are missing', function () {
    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.remove-solo'), [])
        ->assertSessionHasErrors(['trainee_id', 'course_id']);
});

test('mentor without course access cannot remove solo', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();
    $mentor  = trainingHttpMentorNoAccess();

    $this->actingAs($mentor)
        ->post(route('overview.remove-solo'), [
            'trainee_id' => $trainee->id,
            'course_id'  => $course->id,
        ])
        ->assertSessionHasErrors('error');
});

test('superuser can remove a solo endorsement', function () {
    $course  = trainingHttpRtgCourse();
    $trainee = User::factory()->create();

    $clientWithSolo = new class($course->solo_station, $trainee->vatsim_id) extends FakeVatEudClient {
        public function __construct(private string $position, private int $vatsimId) {}

        public function getSoloEndorsements(): array
        {
            return [new \App\Integrations\VatEud\DTOs\SoloEndorsementData(
                id: 1,
                userCid: $this->vatsimId,
                position: $this->position,
                facility: 9,
                mentor: 1441619,
                positionDays: 7,
                expireAt: \Carbon\Carbon::now()->addDays(10),
                createdAt: \Carbon\Carbon::now()->subDays(7),
            )];
        }
    };
    $this->app->instance(VatEudClientInterface::class, $clientWithSolo);

    $this->actingAs(trainingHttpSuperuser())
        ->post(route('overview.remove-solo'), [
            'trainee_id' => $trainee->id,
            'course_id'  => $course->id,
        ])
        ->assertRedirect(route('overview.index', ['last_course_id' => $course->id]));
});

// ─── EndorsementController: removeTier1 ──────────────────────────────────────

test('unauthenticated user is redirected from tier1 removal', function () {
    $activity = EndorsementActivity::create([
        'endorsement_id'   => 1,
        'vatsim_id'        => 1601613,
        'position'         => 'EDDL_TWR',
        'activity_minutes' => 100,
        'last_updated'     => now(),
    ]);

    $this->delete(route('endorsements.tier1.remove', $activity->endorsement_id))->assertRedirect();
});

test('non-mentor cannot remove a tier1 endorsement', function () {
    $activity = EndorsementActivity::create([
        'endorsement_id'   => 1,
        'vatsim_id'        => 1601613,
        'position'         => 'EDDL_TWR',
        'activity_minutes' => 100,
        'last_updated'     => now(),
    ]);

    $this->actingAs(User::factory()->create())
        ->delete(route('endorsements.tier1.remove', $activity->endorsement_id))
        ->assertForbidden();
});

test('superuser can mark an endorsement for removal', function () {
    $activity = EndorsementActivity::create([
        'endorsement_id'   => 1,
        'vatsim_id'        => 1601613,
        'position'         => 'EDDL_TWR',
        'activity_minutes' => 100,
        'last_updated'     => now(),
    ]);

    $this->actingAs(trainingHttpSuperuser())
        ->delete(route('endorsements.tier1.remove', $activity->endorsement_id))
        ->assertRedirect();

    expect($activity->fresh()->removal_date)->not->toBeNull();
});

test('tier1 removal returns error when endorsement does not exist', function () {
    $this->actingAs(trainingHttpSuperuser())
        ->delete(route('endorsements.tier1.remove', 99999))
        ->assertRedirect();
});

// ─── EndorsementController: requestTier2 ─────────────────────────────────────

test('unauthenticated user is redirected from tier2 request', function () {
    $tier2 = Tier2Endorsement::create(['name' => 'Test Tier2', 'position' => 'EDXX_AFIS', 'moodle_course_id' => 0]);

    $this->post(route('endorsements.tier2.request', $tier2->id))->assertRedirect();
});

test('authenticated vatsim user can request a tier2 endorsement', function () {
    $tier2 = Tier2Endorsement::create(['name' => 'Test Tier2 B', 'position' => 'EDXX_NEW1', 'moodle_course_id' => 0]);
    $user  = User::factory()->create(['vatsim_id' => 1234567]);

    $this->actingAs($user)
        ->post(route('endorsements.tier2.request', $tier2->id))
        ->assertRedirect(route('endorsements.trainee'));
});

test('tier2 request fails when user already has the endorsement', function () {
    $tier2 = Tier2Endorsement::create(['name' => 'Test Tier2', 'position' => 'EDXX_AFIS', 'moodle_course_id' => 0]);
    $user  = User::factory()->create(['vatsim_id' => 1441619]);

    $this->actingAs($user)
        ->post(route('endorsements.tier2.request', $tier2->id))
        ->assertRedirect();
});

// ─── MentorManagementController: courses index ────────────────────────────────

test('unauthenticated user is redirected from courses index', function () {
    $this->get(route('courses.index'))->assertRedirect();
});

test('courses index hides courses outside the user rating range', function () {
    $eligibleCourse = Course::factory()->create([
        'type'       => 'RTG',
        'position'   => 'TWR',
        'min_rating' => 2,
        'max_rating' => 3,
    ]);
    $ineligibleCourse = Course::factory()->create([
        'type'       => 'RTG',
        'position'   => 'APP',
        'min_rating' => 4,
        'max_rating' => 5,
    ]);

    $user = User::factory()->create(['subdivision' => 'GER', 'rating' => 3]);
    Http::swap(new \Illuminate\Http\Client\Factory());
    Http::fake(['*' => Http::response(['data' => ['controllers' => [$user->vatsim_id]]], 200)]);
    Cache::flush();

    $this->actingAs($user)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('training/courses')
            ->where('courses', fn ($courses) => collect($courses)->pluck('id')->contains($eligibleCourse->id)
                && !collect($courses)->pluck('id')->contains($ineligibleCourse->id))
        );
});

test('courses index shows other RTG courses when user is actively training in one with can_join false', function () {
    $activeCourse      = Course::factory()->create(['type' => 'RTG', 'position' => 'TWR', 'min_rating' => 2, 'max_rating' => 3]);
    $alternativeCourse = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'min_rating' => 2, 'max_rating' => 3]);

    $user = User::factory()->create(['subdivision' => 'GER', 'rating' => 3]);
    Http::swap(new \Illuminate\Http\Client\Factory());
    Http::fake(['*' => Http::response(['data' => ['controllers' => [$user->vatsim_id]]], 200)]);
    Cache::flush();

    trainingHttpAttachTrainee($activeCourse, $user);

    $this->actingAs($user)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('training/courses')
            ->where('courses', fn ($courses) => collect($courses)->pluck('id')->contains($alternativeCourse->id)
                && collect($courses)->firstWhere('id', $alternativeCourse->id)['can_join'] === false)
        );
});

test('admin user sees all courses regardless of eligibility', function () {
    $lowRatingCourse = Course::factory()->create(['type' => 'RTG', 'position' => 'TWR', 'min_rating' => 2, 'max_rating' => 3]);
    $highRatingCourse = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'min_rating' => 4, 'max_rating' => 5]);

    $admin = User::factory()->superuser()->create(['subdivision' => 'GER', 'rating' => 2]);
    Http::swap(new \Illuminate\Http\Client\Factory());
    Http::fake(['*' => Http::response(['data' => ['controllers' => [$admin->vatsim_id]]], 200)]);
    Cache::flush();

    $this->actingAs($admin)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('training/courses')
            ->where('courses', fn ($courses) => collect($courses)->pluck('id')->contains($lowRatingCourse->id)
                && collect($courses)->pluck('id')->contains($highRatingCourse->id))
        );
});

test('courses index includes courses the user is already on the waiting list for', function () {
    $course = Course::factory()->create([
        'type'       => 'RTG',
        'position'   => 'TWR',
        'min_rating' => 2,
        'max_rating' => 3,
    ]);

    $user = User::factory()->create(['subdivision' => 'GER', 'rating' => 3]);
    Http::fake(['*' => Http::response(['data' => ['controllers' => [$user->vatsim_id]]], 200)]);
    Cache::flush();

    WaitingListEntry::create([
        'course_id'  => $course->id,
        'user_id'    => $user->id,
        'date_added' => now(),
        'activity'   => 0,
    ]);

    $this->actingAs($user)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('training/courses')
            ->where('courses', fn ($courses) => collect($courses)->pluck('id')->contains($course->id))
        );
});
