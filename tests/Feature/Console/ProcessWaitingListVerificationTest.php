<?php

use App\Integrations\Vatger\FakeVatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Models\WaitingListVerificationRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatgerClientInterface::class, FakeVatgerClient::class);
    Event::fake();
});

test('waitinglists:verify-interest purges unconfirmed entries and records a run', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create(['type' => 'RTG']);

    $entry = WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
        'is_interested' => false,
    ]);

    $this->artisan('waitinglists:verify-interest')->assertExitCode(0);

    $this->assertDatabaseMissing('waiting_list_entries', ['id' => $entry->id]);
    $this->assertDatabaseHas('waiting_list_verification_runs', ['year_month' => now()->format('Y-m')]);
});

test('waitinglists:verify-interest is a no-op the second time in the same month', function () {
    $this->artisan('waitinglists:verify-interest')->assertExitCode(0);
    $this->artisan('waitinglists:verify-interest')->assertExitCode(0);

    expect(WaitingListVerificationRun::count())->toBe(1);
});
