<?php

use App\Models\Course;
use App\Models\TrainingLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

function makeLog(array $overrides = []): TrainingLog
{
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $course = Course::factory()->create();

    // All rating columns are NOT NULL in the DB; default to 0 (not rated).
    return TrainingLog::create(array_merge([
        'trainee_id' => $trainee->id,
        'mentor_id' => $mentor->id,
        'course_id' => $course->id,
        'session_date' => now(),
        'position' => 'EDDL_TWR',
        'type' => TrainingLog::TYPE_ONLINE,
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

// ─── Constants ────────────────────────────────────────────────────────────────

test('TYPE_ONLINE constant equals O', function () {
    expect(TrainingLog::TYPE_ONLINE)->toBe('O');
});

test('RATING_NOT_RATED constant equals 0', function () {
    expect(TrainingLog::RATING_NOT_RATED)->toBe(0);
});

test('RATING_MET constant equals 3', function () {
    expect(TrainingLog::RATING_MET)->toBe(3);
});

test('TRAFFIC_LOW constant equals L', function () {
    expect(TrainingLog::TRAFFIC_LOW)->toBe('L');
});

// ─── getAverageRatingAttribute ────────────────────────────────────────────────

test('average_rating excludes zero-rated categories', function () {
    $log = makeLog(['theory' => 3, 'phraseology' => 4, 'coordination' => 0]);

    expect($log->average_rating)->toBe(3.5);
});

test('average_rating is 0.0 when all categories are zero', function () {
    $log = makeLog();

    expect($log->average_rating)->toBe(0.0);
});

test('average_rating uses all non-zero categories', function () {
    $log = makeLog([
        'theory' => 2,
        'phraseology' => 4,
        'coordination' => 3,
    ]);

    expect($log->average_rating)->toBe(3.0);
});

test('average_rating rounds to two decimal places', function () {
    $log = makeLog(['theory' => 1, 'phraseology' => 2, 'coordination' => 3]);

    expect($log->average_rating)->toBe(2.0);
});

// ─── hasRatings ───────────────────────────────────────────────────────────────

test('hasRatings returns false when all categories are zero', function () {
    $log = makeLog();

    expect($log->hasRatings())->toBeFalse();
});

test('hasRatings returns true when any category is non-zero', function () {
    $log = makeLog();
    $log->theory = 2;

    expect($log->hasRatings())->toBeTrue();
});

// ─── getRatingDisplay ─────────────────────────────────────────────────────────

test('getRatingDisplay returns Not Rated for 0', function () {
    expect(TrainingLog::getRatingDisplay(0))->toBe('Not Rated');
});

test('getRatingDisplay returns Requirements Not Met for 1', function () {
    expect(TrainingLog::getRatingDisplay(1))->toBe('Requirements Not Met');
});

test('getRatingDisplay returns Requirements Partially Met for 2', function () {
    expect(TrainingLog::getRatingDisplay(2))->toBe('Requirements Partially Met');
});

test('getRatingDisplay returns Requirements Met for 3', function () {
    expect(TrainingLog::getRatingDisplay(3))->toBe('Requirements Met');
});

test('getRatingDisplay returns Requirements Exceeded for 4', function () {
    expect(TrainingLog::getRatingDisplay(4))->toBe('Requirements Exceeded');
});

// ─── traffic_level_display ────────────────────────────────────────────────────

test('traffic_level_display returns Low for L', function () {
    $log = makeLog();
    $log->traffic_level = 'L';

    expect($log->traffic_level_display)->toBe('Low');
});

test('traffic_level_display returns Medium for M', function () {
    $log = makeLog();
    $log->traffic_level = 'M';

    expect($log->traffic_level_display)->toBe('Medium');
});

test('traffic_level_display returns High for H', function () {
    $log = makeLog();
    $log->traffic_level = 'H';

    expect($log->traffic_level_display)->toBe('High');
});

test('traffic_level_display returns null when traffic_level is not set', function () {
    $log = makeLog();

    expect($log->traffic_level_display)->toBeNull();
});

// ─── Scopes ───────────────────────────────────────────────────────────────────

test('forTrainee scope returns only logs for the given trainee', function () {
    $trainee = User::factory()->create();
    $other = User::factory()->create();
    $mentor = User::factory()->create();
    $course = Course::factory()->create();

    makeLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id]);
    makeLog(['trainee_id' => $other->id,   'mentor_id' => $mentor->id, 'course_id' => $course->id]);

    $results = TrainingLog::forTrainee($trainee->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->trainee_id)->toBe($trainee->id);
});

test('byMentor scope returns only logs created by the given mentor', function () {
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $otherM = User::factory()->create();
    $course = Course::factory()->create();

    makeLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id]);
    makeLog(['trainee_id' => $trainee->id, 'mentor_id' => $otherM->id, 'course_id' => $course->id]);

    $results = TrainingLog::byMentor($mentor->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->mentor_id)->toBe($mentor->id);
});

test('forCourse scope returns only logs for the given course', function () {
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $course = Course::factory()->create();
    $otherCourse = Course::factory()->create();

    makeLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id]);
    makeLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $otherCourse->id]);

    $results = TrainingLog::forCourse($course->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->course_id)->toBe($course->id);
});

test('recent scope orders logs by session_date descending', function () {
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $course = Course::factory()->create();

    $older = makeLog([
        'trainee_id' => $trainee->id,
        'mentor_id' => $mentor->id,
        'course_id' => $course->id,
        'session_date' => now()->subDays(5),
    ]);

    $newer = makeLog([
        'trainee_id' => $trainee->id,
        'mentor_id' => $mentor->id,
        'course_id' => $course->id,
        'session_date' => now(),
    ]);

    $results = TrainingLog::recent()->get();

    expect($results->first()->id)->toBe($newer->id);
    expect($results->last()->id)->toBe($older->id);
});

test('passed scope returns only logs with result true', function () {
    $log1 = makeLog(['result' => true]);
    $log2 = makeLog(['result' => false]);

    $results = TrainingLog::passed()->get();

    expect($results->contains($log1))->toBeTrue();
    expect($results->contains($log2))->toBeFalse();
});

test('failed scope returns only logs with result false', function () {
    $log1 = makeLog(['result' => true]);
    $log2 = makeLog(['result' => false]);

    $results = TrainingLog::failed()->get();

    expect($results->contains($log2))->toBeTrue();
    expect($results->contains($log1))->toBeFalse();
});
