<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

// ─── moodle_course_ids cast ───────────────────────────────────────────────────

test('moodle_course_ids stores and retrieves an array of integers', function () {
    $course = Course::factory()->create(['moodle_course_ids' => [101, 202]]);
    $course->refresh();

    expect($course->moodle_course_ids)->toBe([101, 202]);
});

test('moodle_course_ids returns empty array when stored as null', function () {
    $course = Course::factory()->create(['moodle_course_ids' => null]);

    expect($course->moodle_course_ids)->toBe([]);
});

test('moodle_course_ids converts string integers to integers', function () {
    $course = Course::factory()->create(['moodle_course_ids' => ['101', '202']]);
    $course->refresh();

    expect($course->moodle_course_ids)->toBe([101, 202]);
});

test('moodle_course_ids returns empty array when stored as empty array', function () {
    $course = Course::factory()->create(['moodle_course_ids' => []]);

    expect($course->moodle_course_ids)->toBe([]);
});

// ─── Display attributes ───────────────────────────────────────────────────────

test('type_display returns Rating for RTG', function () {
    $course = Course::factory()->create(['type' => 'RTG']);

    expect($course->type_display)->toBe('Rating');
});

test('type_display returns Endorsement for EDMT', function () {
    $course = Course::factory()->create(['type' => 'EDMT']);

    expect($course->type_display)->toBe('Endorsement');
});

test('type_display returns Visitor for GST', function () {
    $course = Course::factory()->create(['type' => 'GST']);

    expect($course->type_display)->toBe('Visitor');
});

test('type_display returns Familiarisation for FAM', function () {
    $course = Course::factory()->create(['type' => 'FAM']);

    expect($course->type_display)->toBe('Familiarisation');
});

test('type_display returns Roster Reentry for RST', function () {
    $course = Course::factory()->create(['type' => 'RST']);

    expect($course->type_display)->toBe('Roster Reentry');
});

test('position_display returns Tower for TWR', function () {
    $course = Course::factory()->create(['position' => 'TWR']);

    expect($course->position_display)->toBe('Tower');
});

test('position_display returns Ground for GND', function () {
    $course = Course::factory()->create(['position' => 'GND']);

    expect($course->position_display)->toBe('Ground');
});

test('position_display returns Approach for APP', function () {
    $course = Course::factory()->create(['position' => 'APP']);

    expect($course->position_display)->toBe('Approach');
});

test('position_display returns Centre for CTR', function () {
    $course = Course::factory()->create(['position' => 'CTR']);

    expect($course->position_display)->toBe('Centre');
});

// ─── forRating scope ─────────────────────────────────────────────────────────

test('forRating returns course when rating is within min and max', function () {
    Course::factory()->create(['min_rating' => 2, 'max_rating' => 3]);

    expect(Course::forRating(2)->count())->toBe(1);
    expect(Course::forRating(3)->count())->toBe(1);
});

test('forRating excludes course when rating is below min_rating', function () {
    Course::factory()->create(['min_rating' => 2, 'max_rating' => 3]);

    expect(Course::forRating(1)->count())->toBe(0);
});

test('forRating excludes course when rating is above max_rating', function () {
    Course::factory()->create(['min_rating' => 2, 'max_rating' => 3]);

    expect(Course::forRating(4)->count())->toBe(0);
});

// ─── byType scope ─────────────────────────────────────────────────────────────

test('byType returns only courses of the given type', function () {
    Course::factory()->create(['type' => 'RTG']);
    Course::factory()->create(['type' => 'EDMT']);

    $results = Course::byType('RTG')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->type)->toBe('RTG');
});

// ─── Relationships ────────────────────────────────────────────────────────────

test('mentors relationship returns attached mentors', function () {
    $course = Course::factory()->create();
    $mentor = User::factory()->create();
    $course->mentors()->attach($mentor->id);

    expect($course->mentors()->where('users.id', $mentor->id)->exists())->toBeTrue();
});

test('activeTrainees returns only trainees without completed_at', function () {
    $course = Course::factory()->create();
    $active = User::factory()->create();
    $completed = User::factory()->create();

    DB::table('course_trainees')->insert([
        'course_id' => $course->id,
        'user_id' => $active->id,
        'completed_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('course_trainees')->insert([
        'course_id' => $course->id,
        'user_id' => $completed->id,
        'completed_at' => now()->subDay(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = $course->activeTrainees()->get();

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($active->id);
});

test('completedTrainees returns only trainees with completed_at set', function () {
    $course = Course::factory()->create();
    $active = User::factory()->create();
    $completed = User::factory()->create();

    DB::table('course_trainees')->insert([
        'course_id' => $course->id,
        'user_id' => $active->id,
        'completed_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('course_trainees')->insert([
        'course_id' => $course->id,
        'user_id' => $completed->id,
        'completed_at' => now()->subDay(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = $course->completedTrainees()->get();

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($completed->id);
});
