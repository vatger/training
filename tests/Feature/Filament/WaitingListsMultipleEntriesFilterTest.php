<?php

use App\Filament\Resources\WaitingLists\Pages\ListWaitingLists;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

function makeWaitingListEntry(User $user, Course $course, array $overrides = []): WaitingListEntry
{
    return WaitingListEntry::create(array_merge([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ], $overrides));
}

test('user with a single rating entry and a single edmt entry is excluded', function () {
    $user = User::factory()->create();
    $ratingEntry = makeWaitingListEntry($user, Course::factory()->rtg()->create());
    $edmtEntry = makeWaitingListEntry($user, Course::factory()->edmt()->create());

    Livewire::test(ListWaitingLists::class)
        ->filterTable('multiple_entries', true)
        ->assertCanNotSeeTableRecords([$ratingEntry, $edmtEntry]);
});

test('user with two rating entries is included', function () {
    $user = User::factory()->create();
    $entry1 = makeWaitingListEntry($user, Course::factory()->rtg()->create());
    $entry2 = makeWaitingListEntry($user, Course::factory()->rtg()->create());

    Livewire::test(ListWaitingLists::class)
        ->filterTable('multiple_entries', true)
        ->assertCanSeeTableRecords([$entry1, $entry2]);
});

test('user with one edmt and one fam entry is included', function () {
    $user = User::factory()->create();
    $entry1 = makeWaitingListEntry($user, Course::factory()->edmt()->create());
    $entry2 = makeWaitingListEntry($user, Course::factory()->create(['type' => 'FAM']));

    Livewire::test(ListWaitingLists::class)
        ->filterTable('multiple_entries', true)
        ->assertCanSeeTableRecords([$entry1, $entry2]);
});

test('other course types do not count towards the duplicate groups', function () {
    $user = User::factory()->create();
    $entry1 = makeWaitingListEntry($user, Course::factory()->gst()->create());
    $entry2 = makeWaitingListEntry($user, Course::factory()->create(['type' => 'RST']));

    Livewire::test(ListWaitingLists::class)
        ->filterTable('multiple_entries', true)
        ->assertCanNotSeeTableRecords([$entry1, $entry2]);
});

test('filter is off by default and shows all entries', function () {
    $user = User::factory()->create();
    $entry = makeWaitingListEntry($user, Course::factory()->rtg()->create());

    Livewire::test(ListWaitingLists::class)
        ->assertCanSeeTableRecords([$entry]);
});
