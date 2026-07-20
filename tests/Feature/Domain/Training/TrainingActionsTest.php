<?php

use App\Domain\Training\Actions\AddMentorToCourse;
use App\Domain\Training\Actions\AddTraineeToCourse;
use App\Domain\Training\Actions\AssignTrainee;
use App\Domain\Training\Actions\ClaimTrainee;
use App\Domain\Training\Actions\ReactivateTrainee;
use App\Domain\Training\Actions\RemoveMentorFromCourse;
use App\Domain\Training\Actions\RemoveTrainee;
use App\Domain\Training\Actions\StartTraining;
use App\Domain\Training\Actions\UnclaimTrainee;
use App\Domain\Training\Actions\UpdateTraineeRemark;
use App\Domain\Training\Events\MentorAdded;
use App\Domain\Training\Events\MentorRemoved;
use App\Domain\Training\Events\TraineeAddedToCourse;
use App\Domain\Training\Events\TraineeAssigned;
use App\Domain\Training\Events\TraineeClaimed;
use App\Domain\Training\Events\TraineeReactivated;
use App\Domain\Training\Events\TraineeRemarkUpdated;
use App\Domain\Training\Events\TraineeRemoved;
use App\Domain\Training\Events\TraineeUnclaimed;
use App\Domain\Training\Events\TrainingStarted;
use App\Integrations\Moodle\FakeMoodleClient;
use App\Integrations\Moodle\MoodleClientInterface;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(MoodleClientInterface::class, FakeMoodleClient::class);
    $this->app->bind(VatEudClientInterface::class, FakeVatEudClient::class);
});

/**
 * Insert a row directly into course_trainees without going through the Eloquent relationship,
 * so tests can set up any pivot state (including completed entries).
 */
function attachTraineeToCourse(Course $course, User $trainee, array $attributes = []): void
{
    DB::table('course_trainees')->insert(array_merge([
        'course_id' => $course->id,
        'user_id' => $trainee->id,
        'claimed_by_mentor_id' => null,
        'claimed_at' => null,
        'completed_at' => null,
        'remarks' => null,
        'remark_author_id' => null,
        'remark_updated_at' => null,
        'custom_order' => null,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ], $attributes));
}

// ─── AddMentorToCourse ────────────────────────────────────────────────────────

test('AddMentorToCourse attaches mentor to course', function () {
    Event::fake();
    $course = Course::factory()->create();
    $mentor = User::factory()->create();
    $admin = User::factory()->admin()->create();

    app(AddMentorToCourse::class)->execute($course, $mentor, $admin);

    expect($course->mentors()->where('users.id', $mentor->id)->exists())->toBeTrue();
});

test('AddMentorToCourse fires MentorAdded event', function () {
    Event::fake();
    $course = Course::factory()->create();
    $mentor = User::factory()->create();
    $admin = User::factory()->admin()->create();

    app(AddMentorToCourse::class)->execute($course, $mentor, $admin);

    Event::assertDispatched(MentorAdded::class);
});

// ─── RemoveMentorFromCourse ───────────────────────────────────────────────────

test('RemoveMentorFromCourse detaches mentor from course', function () {
    Event::fake();
    $course = Course::factory()->create();
    $mentor = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $course->mentors()->attach($mentor->id);

    app(RemoveMentorFromCourse::class)->execute($course, $mentor, $admin);

    expect($course->mentors()->where('users.id', $mentor->id)->exists())->toBeFalse();
});

test('RemoveMentorFromCourse unsets claimed_by_mentor_id for trainees claimed by that mentor', function () {
    Event::fake();
    $course = Course::factory()->create();
    $mentor = User::factory()->create();
    $trainee = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $course->mentors()->attach($mentor->id);
    attachTraineeToCourse($course, $trainee, [
        'claimed_by_mentor_id' => $mentor->id,
        'claimed_at' => now(),
    ]);

    app(RemoveMentorFromCourse::class)->execute($course, $mentor, $admin);

    $pivot = DB::table('course_trainees')
        ->where('course_id', $course->id)
        ->where('user_id', $trainee->id)
        ->first();

    expect($pivot->claimed_by_mentor_id)->toBeNull();
    expect($pivot->claimed_at)->toBeNull();
});

test('RemoveMentorFromCourse does not unclaim trainees belonging to other mentors', function () {
    Event::fake();
    $course = Course::factory()->create();
    $mentor = User::factory()->create();
    $otherMentor = User::factory()->create();
    $trainee = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $course->mentors()->attach($mentor->id);
    attachTraineeToCourse($course, $trainee, [
        'claimed_by_mentor_id' => $otherMentor->id,
        'claimed_at' => now(),
    ]);

    app(RemoveMentorFromCourse::class)->execute($course, $mentor, $admin);

    $pivot = DB::table('course_trainees')
        ->where('course_id', $course->id)
        ->where('user_id', $trainee->id)
        ->first();

    expect($pivot->claimed_by_mentor_id)->toBe($otherMentor->id);
});

test('RemoveMentorFromCourse fires MentorRemoved event', function () {
    Event::fake();
    $course = Course::factory()->create();
    $mentor = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $course->mentors()->attach($mentor->id);

    app(RemoveMentorFromCourse::class)->execute($course, $mentor, $admin);

    Event::assertDispatched(MentorRemoved::class);
});

// ─── AddTraineeToCourse ───────────────────────────────────────────────────────

test('AddTraineeToCourse adds new trainee to course_trainees', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    app(AddTraineeToCourse::class)->execute($course, $trainee, $mentor);

    expect($course->activeTrainees()->where('users.id', $trainee->id)->exists())->toBeTrue();
});

test('AddTraineeToCourse sets claim on newly added trainee', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    app(AddTraineeToCourse::class)->execute($course, $trainee, $mentor);

    $pivot = DB::table('course_trainees')
        ->where('course_id', $course->id)
        ->where('user_id', $trainee->id)
        ->first();

    expect($pivot->claimed_by_mentor_id)->toBe($mentor->id);
    expect($pivot->claimed_at)->not->toBeNull();
});

test('AddTraineeToCourse removes an existing waiting list entry', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    WaitingListEntry::create([
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 5,
    ]);

    app(AddTraineeToCourse::class)->execute($course, $trainee, $mentor);

    expect(
        WaitingListEntry::where('user_id', $trainee->id)->where('course_id', $course->id)->exists()
    )->toBeFalse();
});

test('AddTraineeToCourse reactivates a completed trainee instead of adding a duplicate row', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee, [
        'completed_at' => now()->subDay(),
        'status' => 'removed',
    ]);

    app(AddTraineeToCourse::class)->execute($course, $trainee, $mentor);

    $rows = DB::table('course_trainees')
        ->where('course_id', $course->id)
        ->where('user_id', $trainee->id)
        ->get();

    expect($rows)->toHaveCount(1);

    $pivot = $rows->first();
    expect($pivot->completed_at)->toBeNull();
    expect($pivot->status)->toBe('active');
    expect($pivot->claimed_by_mentor_id)->toBe($mentor->id);
});

test('AddTraineeToCourse fires TraineeAddedToCourse with wasReactivated=false for a new trainee', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    app(AddTraineeToCourse::class)->execute($course, $trainee, $mentor);

    Event::assertDispatched(TraineeAddedToCourse::class, function (TraineeAddedToCourse $event) {
        return $event->wasReactivated === false;
    });
});

test('AddTraineeToCourse fires TraineeAddedToCourse with wasReactivated=true for a reactivated trainee', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee, [
        'completed_at' => now()->subDay(),
        'status' => 'removed',
    ]);

    app(AddTraineeToCourse::class)->execute($course, $trainee, $mentor);

    Event::assertDispatched(TraineeAddedToCourse::class, function (TraineeAddedToCourse $event) {
        return $event->wasReactivated === true;
    });
});

test('AddTraineeToCourse calls Moodle enrollment when course has moodle_course_ids', function () {
    Event::fake();
    $course = Course::factory()->create(['moodle_course_ids' => [101, 102]]);
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    // FakeMoodleClient is bound; enrollment succeeds silently
    app(AddTraineeToCourse::class)->execute($course, $trainee, $mentor);

    expect($course->activeTrainees()->where('users.id', $trainee->id)->exists())->toBeTrue();
});

test('AddTraineeToCourse skips Moodle enrollment when course has no moodle_course_ids', function () {
    Event::fake();
    $course = Course::factory()->create(['moodle_course_ids' => []]);
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    app(AddTraineeToCourse::class)->execute($course, $trainee, $mentor);

    expect($course->activeTrainees()->where('users.id', $trainee->id)->exists())->toBeTrue();
});

// ─── ClaimTrainee ─────────────────────────────────────────────────────────────

test('ClaimTrainee sets claimed_by_mentor_id and claimed_at on the pivot', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee);

    app(ClaimTrainee::class)->execute($course, $trainee, $mentor);

    $pivot = DB::table('course_trainees')
        ->where('course_id', $course->id)
        ->where('user_id', $trainee->id)
        ->first();

    expect($pivot->claimed_by_mentor_id)->toBe($mentor->id);
    expect($pivot->claimed_at)->not->toBeNull();
});

test('ClaimTrainee fires TraineeClaimed event', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee);

    app(ClaimTrainee::class)->execute($course, $trainee, $mentor);

    Event::assertDispatched(TraineeClaimed::class);
});

// ─── UnclaimTrainee ───────────────────────────────────────────────────────────

test('UnclaimTrainee clears claimed_by_mentor_id and claimed_at', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee, [
        'claimed_by_mentor_id' => $mentor->id,
        'claimed_at' => now(),
    ]);

    app(UnclaimTrainee::class)->execute($course, $trainee, $mentor);

    $pivot = DB::table('course_trainees')
        ->where('course_id', $course->id)
        ->where('user_id', $trainee->id)
        ->first();

    expect($pivot->claimed_by_mentor_id)->toBeNull();
    expect($pivot->claimed_at)->toBeNull();
});

test('UnclaimTrainee fires TraineeUnclaimed event', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee, [
        'claimed_by_mentor_id' => $mentor->id,
        'claimed_at' => now(),
    ]);

    app(UnclaimTrainee::class)->execute($course, $trainee, $mentor);

    Event::assertDispatched(TraineeUnclaimed::class);
});

// ─── AssignTrainee ────────────────────────────────────────────────────────────

test('AssignTrainee updates claimed_by_mentor_id to the new mentor', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $oldMentor = User::factory()->create();
    $newMentor = User::factory()->create();
    $assigningMentor = User::factory()->admin()->create();

    attachTraineeToCourse($course, $trainee, [
        'claimed_by_mentor_id' => $oldMentor->id,
        'claimed_at' => now()->subHour(),
    ]);

    app(AssignTrainee::class)->execute($course, $trainee, $newMentor, $assigningMentor);

    $pivot = DB::table('course_trainees')
        ->where('course_id', $course->id)
        ->where('user_id', $trainee->id)
        ->first();

    expect($pivot->claimed_by_mentor_id)->toBe($newMentor->id);
    expect($pivot->claimed_at)->not->toBeNull();
});

test('AssignTrainee fires TraineeAssigned event', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $newMentor = User::factory()->create();
    $assigningMentor = User::factory()->admin()->create();

    attachTraineeToCourse($course, $trainee);

    app(AssignTrainee::class)->execute($course, $trainee, $newMentor, $assigningMentor);

    Event::assertDispatched(TraineeAssigned::class);
});

// ─── UpdateTraineeRemark ──────────────────────────────────────────────────────

test('UpdateTraineeRemark writes remark, author, and timestamp to pivot', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee);

    app(UpdateTraineeRemark::class)->execute($course, $trainee, $mentor, 'Great progress!');

    $pivot = DB::table('course_trainees')
        ->where('course_id', $course->id)
        ->where('user_id', $trainee->id)
        ->first();

    expect($pivot->remarks)->toBe('Great progress!');
    expect($pivot->remark_author_id)->toBe($mentor->id);
    expect($pivot->remark_updated_at)->not->toBeNull();
});

test('UpdateTraineeRemark overwrites a previous remark', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee, ['remarks' => 'Old remark']);

    app(UpdateTraineeRemark::class)->execute($course, $trainee, $mentor, 'New remark');

    $pivot = DB::table('course_trainees')
        ->where('course_id', $course->id)
        ->where('user_id', $trainee->id)
        ->first();

    expect($pivot->remarks)->toBe('New remark');
});

test('UpdateTraineeRemark fires TraineeRemarkUpdated event', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee);

    app(UpdateTraineeRemark::class)->execute($course, $trainee, $mentor, 'Some remark');

    Event::assertDispatched(TraineeRemarkUpdated::class);
});

// ─── ReactivateTrainee ────────────────────────────────────────────────────────

test('ReactivateTrainee clears completed_at, sets status to active, and claims the trainee', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee, [
        'completed_at' => now()->subDays(10),
        'status' => 'removed',
    ]);

    app(ReactivateTrainee::class)->execute($course, $trainee, $mentor);

    $pivot = DB::table('course_trainees')
        ->where('course_id', $course->id)
        ->where('user_id', $trainee->id)
        ->first();

    expect($pivot->completed_at)->toBeNull();
    expect($pivot->status)->toBe('active');
    expect($pivot->claimed_by_mentor_id)->toBe($mentor->id);
    expect($pivot->claimed_at)->not->toBeNull();
});

test('ReactivateTrainee fires TraineeReactivated event', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee, [
        'completed_at' => now()->subDays(10),
        'status' => 'removed',
    ]);

    app(ReactivateTrainee::class)->execute($course, $trainee, $mentor);

    Event::assertDispatched(TraineeReactivated::class);
});

// ─── RemoveTrainee ────────────────────────────────────────────────────────────

test('RemoveTrainee sets completed_at, status=removed, and clears the claim', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee, [
        'claimed_by_mentor_id' => $mentor->id,
        'claimed_at' => now(),
    ]);

    app(RemoveTrainee::class)->execute($course, $trainee, $mentor);

    $pivot = DB::table('course_trainees')
        ->where('course_id', $course->id)
        ->where('user_id', $trainee->id)
        ->first();

    expect($pivot->completed_at)->not->toBeNull();
    expect($pivot->status)->toBe('removed');
    expect($pivot->claimed_by_mentor_id)->toBeNull();
    expect($pivot->claimed_at)->toBeNull();
});

test('RemoveTrainee fires TraineeRemoved event', function () {
    Event::fake();
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    attachTraineeToCourse($course, $trainee);

    app(RemoveTrainee::class)->execute($course, $trainee, $mentor);

    Event::assertDispatched(TraineeRemoved::class);
});

// ─── StartTraining ────────────────────────────────────────────────────────────

test('StartTraining returns false when RTG course trainee has insufficient activity', function () {
    Event::fake();
    Http::fake();
    $course = Course::factory()->rtg()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    config(['services.training.display_activity' => 8]);

    $entry = WaitingListEntry::create([
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 3,
    ]);

    [$success, $message] = app(StartTraining::class)->execute($entry, $mentor);

    expect($success)->toBeFalse();
    expect($message)->toBe('Trainee does not have sufficient activity to start training.');
});

test('StartTraining does not remove the waiting list entry when activity check fails', function () {
    Event::fake();
    Http::fake();
    $course = Course::factory()->rtg()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    config(['services.training.display_activity' => 8]);

    $entry = WaitingListEntry::create([
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 3,
    ]);

    app(StartTraining::class)->execute($entry, $mentor);

    expect(WaitingListEntry::where('id', $entry->id)->exists())->toBeTrue();
});

test('StartTraining returns true and attaches trainee when RTG activity meets the threshold', function () {
    Event::fake();
    Http::fake();
    $course = Course::factory()->rtg()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    config(['services.training.display_activity' => 8]);

    $entry = WaitingListEntry::create([
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 10,
    ]);

    [$success, $message] = app(StartTraining::class)->execute($entry, $mentor);

    expect($success)->toBeTrue();
    expect($message)->toBe('Training started successfully.');
    expect($course->activeTrainees()->where('users.id', $trainee->id)->exists())->toBeTrue();
});

test('StartTraining deletes the waiting list entry on success', function () {
    Event::fake();
    Http::fake();
    $course = Course::factory()->rtg()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    config(['services.training.display_activity' => 8]);

    $entry = WaitingListEntry::create([
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 10,
    ]);

    $entryId = $entry->id;

    app(StartTraining::class)->execute($entry, $mentor);

    expect(WaitingListEntry::where('id', $entryId)->exists())->toBeFalse();
});

test('StartTraining fires TrainingStarted event on success', function () {
    Event::fake();
    Http::fake();
    $course = Course::factory()->rtg()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    config(['services.training.display_activity' => 8]);

    $entry = WaitingListEntry::create([
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 10,
    ]);

    app(StartTraining::class)->execute($entry, $mentor);

    Event::assertDispatched(TrainingStarted::class);
});

test('StartTraining allows non-RTG courses regardless of activity level', function () {
    Event::fake();
    Http::fake();
    $course = Course::factory()->edmt()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    config(['services.training.display_activity' => 8]);

    $entry = WaitingListEntry::create([
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
    ]);

    [$success] = app(StartTraining::class)->execute($entry, $mentor);

    expect($success)->toBeTrue();
    expect($course->activeTrainees()->where('users.id', $trainee->id)->exists())->toBeTrue();
});

test('StartTraining treats RTG trainee with activity exactly at threshold as sufficient', function () {
    Event::fake();
    Http::fake();
    $course = Course::factory()->rtg()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();

    config(['services.training.display_activity' => 8]);

    $entry = WaitingListEntry::create([
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 8,
    ]);

    [$success] = app(StartTraining::class)->execute($entry, $mentor);

    expect($success)->toBeTrue();
});
