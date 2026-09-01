<?php

use App\Domain\WaitingList\Actions\ProcessMonthlyWaitingListVerification;
use App\Integrations\Vatger\FakeVatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Jobs\ProcessWaitingListVerification;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatgerClientInterface::class, FakeVatgerClient::class);
});

test('handle delegates to ProcessMonthlyWaitingListVerification', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create(['type' => 'RTG']);

    $entry = WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
        'is_interested' => false,
        'removal_date' => now()->subDay(),
    ]);

    app(ProcessWaitingListVerification::class)->handle(app(ProcessMonthlyWaitingListVerification::class));

    $this->assertDatabaseMissing('waiting_list_entries', ['id' => $entry->id]);
});

test('is queueable', function () {
    expect(new ProcessWaitingListVerification)->toBeInstanceOf(ShouldQueue::class);
});
