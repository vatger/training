<?php

use App\Domain\Gdpr\Actions\ApproveGdprRemoval;
use App\Domain\Gdpr\Actions\ReviewGdprItem;
use App\Domain\Gdpr\UserDataMap;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Jobs\AutoRemoveGdprRequest;
use App\Jobs\CancelStaleGdprRemoval;
use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\Course;
use App\Models\GdprRemovalRequest;
use App\Models\Role;
use App\Models\TrainingLog;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatEudClientInterface::class, FakeVatEudClient::class);
    Http::fake(['*' => Http::response([], 200)]);
});

function gdprRequestFor(User $user): GdprRemovalRequest
{
    return GdprRemovalRequest::create([
        'user_id' => $user->id,
        'vatsim_id' => $user->vatsim_id,
        'user_name' => $user->name,
        'search_terms' => UserDataMap::searchTerms($user),
    ]);
}

function gdprApprove(GdprRemovalRequest $request, ?User $approver = null): void
{
    app(ApproveGdprRemoval::class)->execute($request, $approver ?? User::factory()->create(), '127.0.0.1');
}

describe('approving a removal request', function () {
    test('deletes the user and everything that belongs to them', function () {
        $user = User::factory()->create(['vatsim_id' => 7000001, 'email' => 'max@example.com']);
        $other = User::factory()->create();
        $course = Course::factory()->create();
        $role = Role::create(['name' => 'Some Role']);

        $user->roles()->attach($role);
        WaitingListEntry::create(['user_id' => $user->id, 'course_id' => $course->id]);
        $ownLog = createTrainingLog(['trainee_id' => $user->id, 'mentor_id' => $other->id]);
        DB::table('roster_entries')->insert(['user_id' => 7000001, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_settings')->insert(['user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('sessions')->insert(['id' => 's1', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $request = gdprRequestFor($user);
        gdprApprove($request);

        expect(User::find($user->id))->toBeNull()
            ->and(WaitingListEntry::where('user_id', $user->id)->exists())->toBeFalse()
            ->and(TrainingLog::find($ownLog->id))->toBeNull()
            ->and(DB::table('user_roles')->where('user_id', $user->id)->exists())->toBeFalse()
            ->and(DB::table('roster_entries')->where('user_id', 7000001)->exists())->toBeFalse()
            ->and(DB::table('user_settings')->where('user_id', $user->id)->exists())->toBeFalse()
            ->and(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse()
            ->and($other->fresh())->not->toBeNull();
    });

    test('keeps records of other users and shows the author as GDPR Removed', function () {
        $mentor = User::factory()->create(['vatsim_id' => 7000002]);
        $trainee = User::factory()->create();
        $log = createTrainingLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id]);
        DB::table('cpts')->insert([
            'trainee_id' => $trainee->id, 'examiner_id' => $mentor->id, 'local_id' => $mentor->id,
            'date' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        gdprApprove(gdprRequestFor($mentor));

        $log->refresh();
        expect($log->mentor->name)->toBe('GDPR Removed')
            ->and($log->mentor->vatsim_id)->toBeNull()
            ->and($log->mentor->roles)->toBeEmpty()
            ->and(DB::table('cpts')->where('examiner_id', $log->mentor_id)->where('local_id', $log->mentor_id)->count())->toBe(1);
    });

    test('creates review items for authored text and closes only after they are reviewed', function () {
        $mentor = User::factory()->create(['vatsim_id' => 7000003]);
        $trainee = User::factory()->create();
        $log = createTrainingLog([
            'trainee_id' => $trainee->id, 'mentor_id' => $mentor->id,
            'final_comment' => "Danke {$mentor->name}, gut gemacht",
        ]);

        $request = gdprRequestFor($mentor);
        gdprApprove($request);
        $request->refresh();

        expect($request->isApproved())->toBeTrue()
            ->and($request->reviewItems)->toHaveCount(1);

        $item = $request->reviewItems->first();
        expect($item->table_name)->toBe('training_logs')->and($item->record_id)->toBe($log->id)->and($item->reason)->toBe('author');

        $reviewer = User::factory()->create();
        app(ReviewGdprItem::class)->execute($item, $reviewer, ['final_comment' => 'Gut gemacht', 'trainee_id' => 999]);

        $log->refresh();
        expect($log->final_comment)->toBe('Gut gemacht')
            ->and($log->trainee_id)->toBe($trainee->id) // only whitelisted text columns are writable
            ->and($request->fresh()->isCompleted())->toBeTrue()
            ->and($request->fresh()->search_terms)->toBeNull()
            ->and($request->fresh()->user_name)->toBeNull()
            ->and($request->fresh()->vatsim_id)->toBe(7000003);
    });

    test('flags other peoples records whose text mentions the user', function () {
        $user = User::factory()->create(['vatsim_id' => 7000004, 'first_name' => 'Erika', 'last_name' => 'Musterfrau']);
        $trainee = User::factory()->create();
        $mentor = User::factory()->create();
        createTrainingLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'internal_remarks' => 'Unbedenklich']);
        $mentioning = createTrainingLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'next_step' => 'Mit Erika Musterfrau Session fliegen']);

        $request = gdprRequestFor($user);
        gdprApprove($request);

        $items = $request->fresh()->reviewItems;
        expect($items)->toHaveCount(1)
            ->and($items->first()->record_id)->toBe($mentioning->id)
            ->and($items->first()->reason)->toBe('mention');
    });

    test('completes immediately when nothing needs a review', function () {
        $user = User::factory()->create(['vatsim_id' => 7000005]);

        $request = gdprRequestFor($user);
        gdprApprove($request);

        expect($request->fresh()->isCompleted())->toBeTrue();

        apiCreateKeyForGdpr();
        $this->withHeaders(['Authorization' => 'Bearer gdpr-key'])->deleteJson('/api/gdpr-removal/7000005')->assertOk();
    });

    test('every poll refreshes last_polled_at of the open request', function () {
        User::factory()->create(['vatsim_id' => 7000020]);
        apiCreateKeyForGdpr();

        $this->withHeaders(['Authorization' => 'Bearer gdpr-key'])->deleteJson('/api/gdpr-removal/7000020')->assertStatus(202);
        GdprRemovalRequest::sole()->update(['last_polled_at' => now()->subDays(GdprRemovalRequest::STALE_AFTER_DAYS - 1)]);

        $this->withHeaders(['Authorization' => 'Bearer gdpr-key'])->deleteJson('/api/gdpr-removal/7000020')->assertStatus(202);

        expect(GdprRemovalRequest::sole()->last_polled_at->isToday())->toBeTrue();
    });

    test('queues a job for each request not polled for 3 days, which cancels it and wipes the identifiers', function () {
        $stale = gdprRequestFor(User::factory()->create());
        $stale->update(['last_polled_at' => now()->subDays(GdprRemovalRequest::STALE_AFTER_DAYS)]);
        $fresh = gdprRequestFor(User::factory()->create());
        $fresh->update(['last_polled_at' => now()->subDays(GdprRemovalRequest::STALE_AFTER_DAYS - 1)]);

        Queue::fake();
        CancelStaleGdprRemoval::dispatchForStale();

        Queue::assertPushed(CancelStaleGdprRemoval::class, 1);
        (new CancelStaleGdprRemoval($stale->id))->handle();
        (new CancelStaleGdprRemoval($fresh->id))->handle();

        expect($stale->fresh())
            ->isCancelled()->toBeTrue()
            ->cancelled_at->not->toBeNull()
            ->user_name->toBeNull()
            ->search_terms->toBeNull()
            ->and($fresh->fresh()->isPending())->toBeTrue();
    });

    test('approved requests are never cancelled', function () {
        $request = gdprRequestFor(User::factory()->create());
        $request->update(['status' => GdprRemovalRequest::STATUS_APPROVED, 'last_polled_at' => now()->subDays(30)]);

        (new CancelStaleGdprRemoval($request->id))->handle();

        expect($request->fresh()->isApproved())->toBeTrue();
    });

    test('a poll after the cancellation opens a new request', function () {
        $user = User::factory()->create(['vatsim_id' => 7000021]);
        gdprRequestFor($user)->update(['last_polled_at' => now()->subDays(GdprRemovalRequest::STALE_AFTER_DAYS)]);
        CancelStaleGdprRemoval::dispatchForStale();
        apiCreateKeyForGdpr();

        $this->withHeaders(['Authorization' => 'Bearer gdpr-key'])->deleteJson('/api/gdpr-removal/7000021')->assertStatus(202);

        expect(GdprRemovalRequest::open()->count())->toBe(1)
            ->and(GdprRemovalRequest::count())->toBe(2);
    });

    test('keeps answering 202 until the last review item is done', function () {
        $mentor = User::factory()->create(['vatsim_id' => 7000006]);
        createTrainingLog(['trainee_id' => User::factory()->create()->id, 'mentor_id' => $mentor->id]);
        apiCreateKeyForGdpr();

        $this->withHeaders(['Authorization' => 'Bearer gdpr-key'])->deleteJson('/api/gdpr-removal/7000006')->assertStatus(202);
        $request = GdprRemovalRequest::sole();
        gdprApprove($request);

        $this->withHeaders(['Authorization' => 'Bearer gdpr-key'])->deleteJson('/api/gdpr-removal/7000006')->assertStatus(202)->assertJson(['status' => 'approved']);

        app(ReviewGdprItem::class)->execute($request->reviewItems()->first(), User::factory()->create());

        $this->withHeaders(['Authorization' => 'Bearer gdpr-key'])->deleteJson('/api/gdpr-removal/7000006')->assertOk();
        expect(GdprRemovalRequest::count())->toBe(1);
    });

    test('activity logs about the user are deleted and mentions elsewhere are redacted, the audit entry keeps the id', function () {
        $user = User::factory()->create(['vatsim_id' => 7000007, 'first_name' => 'Erika', 'last_name' => 'Musterfrau']);
        $other = User::factory()->create();

        $own = ActivityLog::create(['action' => 'training_log.created', 'user_id' => $user->id, 'description' => 'Erika Musterfrau created a log']);
        $mention = ActivityLog::create([
            'action' => 'cpt.updated', 'user_id' => $other->id,
            'description' => 'Erika Musterfrau was added to a CPT',
            'properties' => ['causer_name' => 'Erika Musterfrau', 'vatsim_id' => 7000007, 'unrelated' => 42],
        ]);

        gdprApprove(gdprRequestFor($user));

        $mention->refresh();
        expect(ActivityLog::find($own->id))->toBeNull()
            ->and($mention->description)->toBe('GDPR Removed was added to a CPT')
            ->and($mention->properties)->toBe(['causer_name' => 'GDPR Removed', 'vatsim_id' => 'GDPR Removed', 'unrelated' => 42]);

        $audit = ActivityLog::where('action', 'gdpr.deletion')->sole();
        expect($audit->properties['vatsim_id'])->toBe(7000007)
            ->and(json_encode($audit->properties))->not->toContain('Erika')
            ->and($audit->description)->not->toContain('Erika');
    });

    test('a request can only be approved once and not by the person it is about', function () {
        $user = User::factory()->create(['vatsim_id' => 7000008]);
        $request = gdprRequestFor($user);

        expect(fn () => gdprApprove($request, $user))->toThrow(DomainException::class);
        expect(User::find($user->id))->not->toBeNull();

        gdprApprove($request);
        expect(fn () => gdprApprove($request->fresh()))->toThrow(DomainException::class);
    });

    test('removes the visitor from VatEUD', function () {
        $client = Mockery::mock(FakeVatEudClient::class)->makePartial();
        $client->shouldReceive('deleteVisitor')->once()->with(7000009)->andReturn(true);
        $this->app->instance(VatEudClientInterface::class, $client);

        gdprApprove(gdprRequestFor(User::factory()->create(['vatsim_id' => 7000009, 'subdivision' => 'USA'])));
    });

    test('does not touch VatEUD visitors for a GER user', function () {
        $client = Mockery::mock(FakeVatEudClient::class)->makePartial();
        $client->shouldReceive('deleteVisitor')->never();
        $this->app->instance(VatEudClientInterface::class, $client);

        gdprApprove(gdprRequestFor(User::factory()->create(['vatsim_id' => 7000010, 'subdivision' => 'GER'])));
    });

    test('the audit entry survives a failure while deleting', function () {
        $user = User::factory()->create(['vatsim_id' => 7000011]);
        $request = gdprRequestFor($user);

        // Makes the delete inside the transaction blow up.
        DB::statement('CREATE TRIGGER gdpr_block BEFORE DELETE ON users BEGIN SELECT RAISE(ABORT, \'blocked\'); END');

        expect(fn () => gdprApprove($request))->toThrow(Exception::class);

        expect(ActivityLog::where('action', 'gdpr.deletion')->exists())->toBeTrue()
            ->and($request->fresh()->isPending())->toBeTrue()
            ->and(User::find($user->id))->not->toBeNull();
    });
});

describe('data map', function () {
    test('every foreign key to users is covered by a GDPR source', function () {
        $covered = collect(UserDataMap::owned())
            ->flatMap(fn ($source) => collect($source['user_columns'])->map(fn ($column) => "{$source['table']}.{$column}"))
            ->merge(collect(UserDataMap::authored())->map(fn ($source) => "{$source['table']}.{$source['column']}"))
            // The records of the removal itself point at reviewers and are nulled when those are deleted.
            ->merge(['gdpr_removal_requests.approved_by_id', 'gdpr_review_items.reviewed_by_id']);

        $foreignKeys = collect(Schema::getTables())
            ->flatMap(fn ($table) => collect(Schema::getForeignKeys($table['name']))
                ->filter(fn ($fk) => $fk['foreign_table'] === 'users')
                ->flatMap(fn ($fk) => collect($fk['columns'])->map(fn ($column) => "{$table['name']}.{$column}")));

        expect($foreignKeys->diff($covered)->values()->all())->toBe([], 'A table references users but is not part of UserDataMap.');
    });

    test('export holds the profile without secrets and rows from every source', function () {
        $user = User::factory()->create(['vatsim_id' => 7000012]);
        $mentor = User::factory()->create();
        $course = Course::factory()->create();
        WaitingListEntry::create(['user_id' => $user->id, 'course_id' => $course->id]);
        createTrainingLog(['trainee_id' => $mentor->id, 'mentor_id' => $user->id]);

        $data = UserDataMap::collect($user, full: true);

        expect($data['profile'])->not->toHaveKeys(['password', 'remember_token'])
            ->and($data['sections']['waiting_list_entries']['rows'])->toHaveCount(1)
            ->and($data['sections']['training_logs_as_mentor']['rows'])->toHaveCount(1)
            ->and($data['sections']['training_logs_as_mentor']['rows'][0])->toHaveKey('final_comment');
    });

    test('the overview resolves ids to names', function () {
        $user = User::factory()->create(['vatsim_id' => 7000013]);
        $course = Course::factory()->create(['name' => 'EDDF Tower']);
        WaitingListEntry::create(['user_id' => $user->id, 'course_id' => $course->id]);

        $row = UserDataMap::collect($user)['sections']['waiting_list_entries']['rows'][0];

        expect($row)->toHaveKey('course')->and($row['course'])->toBe('EDDF Tower')
            ->and($row['involved_user'])->toBe("{$user->first_name} {$user->last_name} (7000013)");

        // The export keeps the raw rows.
        expect(UserDataMap::collect($user, full: true)['sections']['waiting_list_entries']['rows'][0])->not->toHaveKey('involved_user');
    });
});

function apiCreateKeyForGdpr(): void
{
    ApiKey::create(['name' => 'Gdpr', 'key' => 'gdpr-key', 'is_active' => true, 'permissions' => ['gdpr.delete']]);
}

describe('automatic removal of users without data', function () {
    test('removes a user that has only the user model after 2 days', function () {
        $user = User::factory()->create(['vatsim_id' => 7000030]);
        DB::table('user_settings')->insert(['user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
        $request = gdprRequestFor($user);
        $request->forceFill(['created_at' => now()->subDays(GdprRemovalRequest::AUTO_REMOVE_AFTER_DAYS)])->save();

        Queue::fake();
        AutoRemoveGdprRequest::dispatchForDue();
        Queue::assertPushed(AutoRemoveGdprRequest::class, 1);
        (new AutoRemoveGdprRequest($request->id))->handle(app(ApproveGdprRemoval::class));

        expect(User::find($user->id))->toBeNull()
            ->and($request->fresh())->isCompleted()->toBeTrue()
            ->approved_by_id->toBeNull();
    });

    test('does nothing before 2 days', function () {
        $user = User::factory()->create(['vatsim_id' => 7000031]);
        $request = gdprRequestFor($user);
        $request->forceFill(['created_at' => now()->subDays(GdprRemovalRequest::AUTO_REMOVE_AFTER_DAYS - 1)])->save();

        Queue::fake();
        AutoRemoveGdprRequest::dispatchForDue();
        (new AutoRemoveGdprRequest($request->id))->handle(app(ApproveGdprRemoval::class));

        Queue::assertNothingPushed();
        expect(User::find($user->id))->not->toBeNull()->and($request->fresh()->isPending())->toBeTrue();
    });

    test('leaves users with special data for a person to handle', function () {
        $user = User::factory()->create(['vatsim_id' => 7000032]);
        $mentor = User::factory()->create();
        createTrainingLog(['trainee_id' => $user->id, 'mentor_id' => $mentor->id]);
        $request = gdprRequestFor($user);
        $request->forceFill(['created_at' => now()->subDays(30)])->save();

        (new AutoRemoveGdprRequest($request->id))->handle(app(ApproveGdprRemoval::class));

        expect(User::find($user->id))->not->toBeNull()->and($request->fresh()->isPending())->toBeTrue();
    });

    test('a user mentioned in other peoples text counts as special data', function () {
        $user = User::factory()->create(['vatsim_id' => 7000033, 'first_name' => 'Erika', 'last_name' => 'Musterfrau']);
        $trainee = User::factory()->create();
        $mentor = User::factory()->create();
        createTrainingLog(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'next_step' => 'Mit Erika Musterfrau fliegen']);

        expect(UserDataMap::hasSpecialData($user))->toBeTrue();
    });
});
