<?php

use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

function makeEntry(User $user, Course $course, array $overrides = []): WaitingListEntry
{
    return WaitingListEntry::create(array_merge([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ], $overrides));
}

// ─── getWaitingTimeAttribute ──────────────────────────────────────────────────

test('waiting_time is "Today" when added today', function () {
    $entry = makeEntry(User::factory()->create(), Course::factory()->create());

    expect($entry->waiting_time)->toBe('Today');
});

test('waiting_time is "1 day" when added 1 day ago', function () {
    $entry = makeEntry(User::factory()->create(), Course::factory()->create(), [
        'date_added' => now()->subDays(1),
    ]);

    expect($entry->waiting_time)->toBe('1 day');
});

test('waiting_time shows days when added 5 days ago', function () {
    $entry = makeEntry(User::factory()->create(), Course::factory()->create(), [
        'date_added' => now()->subDays(5),
    ]);

    expect($entry->waiting_time)->toBe('5 days');
});

test('waiting_time shows weeks and remainder days when added 10 days ago', function () {
    $entry = makeEntry(User::factory()->create(), Course::factory()->create(), [
        'date_added' => now()->subDays(10),
    ]);

    expect($entry->waiting_time)->toBe('1 week, 3d');
});

test('waiting_time shows exact weeks when added 14 days ago', function () {
    $entry = makeEntry(User::factory()->create(), Course::factory()->create(), [
        'date_added' => now()->subDays(14),
    ]);

    expect($entry->waiting_time)->toBe('2 weeks');
});

test('waiting_time shows months and remainder days when added 35 days ago', function () {
    $entry = makeEntry(User::factory()->create(), Course::factory()->create(), [
        'date_added' => now()->subDays(35),
    ]);

    expect($entry->waiting_time)->toBe('1 month, 5d');
});

test('waiting_time shows year format when added 400 days ago', function () {
    $entry = makeEntry(User::factory()->create(), Course::factory()->create(), [
        'date_added' => now()->subDays(400),
    ]);

    // 400 days = 1 year (365), 35 remaining → 1 remaining month
    expect($entry->waiting_time)->toBe('1 year, 1mo');
});

test('waiting_time shows multiple weeks without remainder', function () {
    $entry = makeEntry(User::factory()->create(), Course::factory()->create(), [
        'date_added' => now()->subDays(21),
    ]);

    expect($entry->waiting_time)->toBe('3 weeks');
});

test('waiting_time shows multiple months with remainder days', function () {
    $entry = makeEntry(User::factory()->create(), Course::factory()->create(), [
        'date_added' => now()->subDays(65),
    ]);

    // 65 days = 2 months (60), 5 remaining
    expect($entry->waiting_time)->toBe('2 months, 5d');
});

// ─── getPositionInQueueAttribute ─────────────────────────────────────────────

test('position_in_queue reflects chronological order within the same course', function () {
    $course = Course::factory()->create();
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $u3 = User::factory()->create();

    $entry1 = makeEntry($u1, $course, ['date_added' => now()->subDays(10)]);
    $entry2 = makeEntry($u2, $course, ['date_added' => now()->subDays(5)]);
    $entry3 = makeEntry($u3, $course, ['date_added' => now()]);

    expect($entry1->position_in_queue)->toBe(1);
    expect($entry2->position_in_queue)->toBe(2);
    expect($entry3->position_in_queue)->toBe(3);
});

test('position_in_queue is 1 for the only entry in a course', function () {
    $entry = makeEntry(User::factory()->create(), Course::factory()->create());

    expect($entry->position_in_queue)->toBe(1);
});

test('position_in_queue is isolated per course', function () {
    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();

    $entryA = makeEntry($u1, $courseA, ['date_added' => now()->subDays(5)]);
    $entryB = makeEntry($u2, $courseB, ['date_added' => now()]);

    expect($entryA->position_in_queue)->toBe(1);
    expect($entryB->position_in_queue)->toBe(1);
});
