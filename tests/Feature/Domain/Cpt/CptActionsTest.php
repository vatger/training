<?php

use App\Domain\Cpt\Actions\CreateCpt;
use App\Domain\Cpt\Actions\GradeCpt;
use App\Domain\Cpt\Actions\JoinCptAsExaminer;
use App\Domain\Cpt\Actions\JoinCptAsLocal;
use App\Domain\Cpt\Actions\LeaveCptAsExaminer;
use App\Domain\Cpt\Actions\LeaveCptAsLocal;
use App\Domain\Cpt\Actions\UploadCptLog;
use App\Domain\Cpt\Events\CptCreated;
use App\Domain\Cpt\Events\CptExaminerJoined;
use App\Domain\Cpt\Events\CptExaminerLeft;
use App\Domain\Cpt\Events\CptGraded;
use App\Domain\Cpt\Events\CptLocalJoined;
use App\Domain\Cpt\Events\CptLocalLeft;
use App\Domain\Cpt\Events\CptLogUploaded;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Integrations\Vatger\FakeVatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\Course;
use App\Models\Cpt;
use App\Models\CptLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatEudClientInterface::class, FakeVatEudClient::class);
    $this->app->bind(VatgerClientInterface::class, FakeVatgerClient::class);

    // Allow Cpt Eloquent model events so the saving hook (which sets confirmed)
    // still fires. Fake everything else to suppress LogsActivity on Course
    // (which references App\Services\ActivityLogger, absent on this branch)
    // and to record application events for assertDispatched() checks.
    Event::fakeExcept([
        'eloquent.creating: App\Models\Cpt',
        'eloquent.created: App\Models\Cpt',
        'eloquent.updating: App\Models\Cpt',
        'eloquent.updated: App\Models\Cpt',
        'eloquent.saving: App\Models\Cpt',
        'eloquent.saved: App\Models\Cpt',
        'eloquent.creating: App\Models\CptLog',
        'eloquent.created: App\Models\CptLog',
        'eloquent.updating: App\Models\CptLog',
        'eloquent.updated: App\Models\CptLog',
        'eloquent.saving: App\Models\CptLog',
        'eloquent.saved: App\Models\CptLog',
    ]);
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Creates a Cpt record directly, bypassing the CreateCpt action.
 * Must be called while Event::fakeExcept is active (so Cpt saving hook runs).
 */
function makeCpt(array $attributes = []): Cpt
{
    $course = Course::factory()->create(['solo_station' => 'EDDF_TWR']);
    $trainee = User::factory()->create();

    return Cpt::create(array_merge([
        'course_id' => $course->id,
        'trainee_id' => $trainee->id,
        'date' => now()->addDays(7),
        'examiner_id' => null,
        'local_id' => null,
    ], $attributes));
}

// ─── CreateCpt ────────────────────────────────────────────────────────────────

test('CreateCpt creates a cpt without examiner or local', function () {
    $course = Course::factory()->create(['solo_station' => 'EDDF_TWR']);
    $trainee = User::factory()->create();
    $creator = User::factory()->create();

    $cpt = app(CreateCpt::class)->execute($course, $trainee, now()->addDays(7)->toDateString(), $creator);

    expect($cpt)->toBeInstanceOf(Cpt::class);
    expect($cpt->trainee_id)->toBe($trainee->id);
    expect($cpt->course_id)->toBe($course->id);
    expect($cpt->examiner_id)->toBeNull();
    expect($cpt->local_id)->toBeNull();
    expect($cpt->fresh()->confirmed)->toBeFalse();
    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'trainee_id' => $trainee->id]);
});

test('CreateCpt creates a confirmed cpt when both examiner and local are provided', function () {
    $course = Course::factory()->create(['solo_station' => 'EDDF_TWR']);
    $trainee = User::factory()->create();
    $creator = User::factory()->create();
    $examiner = User::factory()->create();
    $local = User::factory()->create();

    $cpt = app(CreateCpt::class)->execute($course, $trainee, now()->addDays(7)->toDateString(), $creator, $examiner, $local);

    expect($cpt->examiner_id)->toBe($examiner->id);
    expect($cpt->local_id)->toBe($local->id);
    expect($cpt->fresh()->confirmed)->toBeTrue();
});

test('CreateCpt fires CptCreated event', function () {
    $course = Course::factory()->create(['solo_station' => 'EDDF_TWR']);
    $trainee = User::factory()->create();
    $creator = User::factory()->create();

    app(CreateCpt::class)->execute($course, $trainee, now()->addDays(7)->toDateString(), $creator);

    Event::assertDispatched(CptCreated::class);
});

test('CreateCpt returns the created Cpt instance', function () {
    $course = Course::factory()->create(['solo_station' => 'EDDF_TWR']);
    $trainee = User::factory()->create();
    $creator = User::factory()->create();

    $result = app(CreateCpt::class)->execute($course, $trainee, now()->addDays(7)->toDateString(), $creator);

    expect($result)->toBeInstanceOf(Cpt::class);
    expect($result->exists)->toBeTrue();
});

// ─── JoinCptAsExaminer ────────────────────────────────────────────────────────

test('JoinCptAsExaminer sets examiner_id on the cpt', function () {
    $cpt = makeCpt();
    $examiner = User::factory()->create();

    app(JoinCptAsExaminer::class)->execute($cpt, $examiner);

    expect($cpt->fresh()->examiner_id)->toBe($examiner->id);
    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'examiner_id' => $examiner->id]);
});

test('JoinCptAsExaminer confirms the cpt when local is already assigned', function () {
    $local = User::factory()->create();
    $cpt = makeCpt(['local_id' => $local->id]);

    expect($cpt->local_id)->toBe($local->id);
    expect($cpt->examiner_id)->toBeNull();
    expect($cpt->fresh()->confirmed)->toBeFalse();

    $examiner = User::factory()->create();
    app(JoinCptAsExaminer::class)->execute($cpt, $examiner);

    expect($cpt->fresh()->confirmed)->toBeTrue();
});

test('JoinCptAsExaminer does not set confirmed when local is not assigned', function () {
    $cpt = makeCpt();
    $examiner = User::factory()->create();

    app(JoinCptAsExaminer::class)->execute($cpt, $examiner);

    expect($cpt->fresh()->confirmed)->toBeFalse();
});

test('JoinCptAsExaminer fires CptExaminerJoined event', function () {
    $cpt = makeCpt();
    $examiner = User::factory()->create();

    app(JoinCptAsExaminer::class)->execute($cpt, $examiner);

    Event::assertDispatched(CptExaminerJoined::class);
});

// ─── JoinCptAsLocal ───────────────────────────────────────────────────────────

test('JoinCptAsLocal sets local_id on the cpt', function () {
    $cpt = makeCpt();
    $local = User::factory()->create();

    app(JoinCptAsLocal::class)->execute($cpt, $local);

    expect($cpt->fresh()->local_id)->toBe($local->id);
    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'local_id' => $local->id]);
});

test('JoinCptAsLocal confirms the cpt when examiner is already assigned', function () {
    $examiner = User::factory()->create();
    $cpt = makeCpt(['examiner_id' => $examiner->id]);

    expect($cpt->examiner_id)->toBe($examiner->id);
    expect($cpt->local_id)->toBeNull();
    expect($cpt->fresh()->confirmed)->toBeFalse();

    $local = User::factory()->create();
    app(JoinCptAsLocal::class)->execute($cpt, $local);

    expect($cpt->fresh()->confirmed)->toBeTrue();
});

test('JoinCptAsLocal does not set confirmed when examiner is not assigned', function () {
    $cpt = makeCpt();
    $local = User::factory()->create();

    app(JoinCptAsLocal::class)->execute($cpt, $local);

    expect($cpt->fresh()->confirmed)->toBeFalse();
});

test('JoinCptAsLocal fires CptLocalJoined event', function () {
    $cpt = makeCpt();
    $local = User::factory()->create();

    app(JoinCptAsLocal::class)->execute($cpt, $local);

    Event::assertDispatched(CptLocalJoined::class);
});

// ─── LeaveCptAsExaminer ───────────────────────────────────────────────────────

test('LeaveCptAsExaminer clears examiner_id', function () {
    $examiner = User::factory()->create();
    $cpt = makeCpt(['examiner_id' => $examiner->id]);

    app(LeaveCptAsExaminer::class)->execute($cpt, $examiner);

    expect($cpt->fresh()->examiner_id)->toBeNull();
    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'examiner_id' => null]);
});

test('LeaveCptAsExaminer sets confirmed to false when cpt was confirmed', function () {
    $examiner = User::factory()->create();
    $local = User::factory()->create();
    $cpt = makeCpt(['examiner_id' => $examiner->id, 'local_id' => $local->id]);

    expect($cpt->fresh()->confirmed)->toBeTrue();

    app(LeaveCptAsExaminer::class)->execute($cpt, $examiner);

    expect($cpt->fresh()->confirmed)->toBeFalse();
});

test('LeaveCptAsExaminer fires CptExaminerLeft event', function () {
    $examiner = User::factory()->create();
    $cpt = makeCpt(['examiner_id' => $examiner->id]);

    app(LeaveCptAsExaminer::class)->execute($cpt, $examiner);

    Event::assertDispatched(CptExaminerLeft::class);
});

// ─── LeaveCptAsLocal ──────────────────────────────────────────────────────────

test('LeaveCptAsLocal clears local_id', function () {
    $local = User::factory()->create();
    $cpt = makeCpt(['local_id' => $local->id]);

    app(LeaveCptAsLocal::class)->execute($cpt, $local);

    expect($cpt->fresh()->local_id)->toBeNull();
    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'local_id' => null]);
});

test('LeaveCptAsLocal sets confirmed to false when cpt was confirmed', function () {
    $examiner = User::factory()->create();
    $local = User::factory()->create();
    $cpt = makeCpt(['examiner_id' => $examiner->id, 'local_id' => $local->id]);

    expect($cpt->fresh()->confirmed)->toBeTrue();

    app(LeaveCptAsLocal::class)->execute($cpt, $local);

    expect($cpt->fresh()->confirmed)->toBeFalse();
});

test('LeaveCptAsLocal fires CptLocalLeft event', function () {
    $local = User::factory()->create();
    $cpt = makeCpt(['local_id' => $local->id]);

    app(LeaveCptAsLocal::class)->execute($cpt, $local);

    Event::assertDispatched(CptLocalLeft::class);
});

// ─── UploadCptLog ─────────────────────────────────────────────────────────────

test('UploadCptLog stores the file on the private disk', function () {
    Storage::fake('private');

    $cpt = makeCpt();
    $uploader = User::factory()->create();
    $file = UploadedFile::fake()->create('log.txt', 10);

    $log = app(UploadCptLog::class)->execute($cpt, $uploader, $file);

    Storage::disk('private')->assertExists($log->log_file);
});

test('UploadCptLog creates a CptLog record in the database', function () {
    Storage::fake('private');

    $cpt = makeCpt();
    $uploader = User::factory()->create();
    $file = UploadedFile::fake()->create('log.txt', 10);

    $log = app(UploadCptLog::class)->execute($cpt, $uploader, $file);

    expect($log)->toBeInstanceOf(CptLog::class);
    $this->assertDatabaseHas('cpt_logs', [
        'id' => $log->id,
        'cpt_id' => $cpt->id,
        'uploaded_by_id' => $uploader->id,
    ]);
});

test('UploadCptLog sets log_uploaded to true on the cpt', function () {
    Storage::fake('private');

    $cpt = makeCpt();
    $uploader = User::factory()->create();
    $file = UploadedFile::fake()->create('log.txt', 10);

    expect($cpt->fresh()->log_uploaded)->toBeFalse();

    app(UploadCptLog::class)->execute($cpt, $uploader, $file);

    expect($cpt->fresh()->log_uploaded)->toBeTrue();
});

test('UploadCptLog fires CptLogUploaded event', function () {
    Storage::fake('private');

    $cpt = makeCpt();
    $uploader = User::factory()->create();
    $file = UploadedFile::fake()->create('log.txt', 10);

    app(UploadCptLog::class)->execute($cpt, $uploader, $file);

    Event::assertDispatched(CptLogUploaded::class);
});

test('UploadCptLog returns the created CptLog', function () {
    Storage::fake('private');

    $cpt = makeCpt();
    $uploader = User::factory()->create();
    $file = UploadedFile::fake()->create('log.txt', 10);

    $result = app(UploadCptLog::class)->execute($cpt, $uploader, $file);

    expect($result)->toBeInstanceOf(CptLog::class);
    expect($result->cpt_id)->toBe($cpt->id);
    expect($result->uploaded_by_id)->toBe($uploader->id);
    expect($result->log_file)->toStartWith('cpt_logs/');
});

// ─── GradeCpt ─────────────────────────────────────────────────────────────────

test('GradeCpt sets passed to true', function () {
    $cpt = makeCpt();
    $grader = User::factory()->create();

    app(GradeCpt::class)->execute($cpt, true, $grader);

    expect($cpt->fresh()->passed)->toBeTrue();
    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'passed' => true]);
});

test('GradeCpt sets passed to false', function () {
    $cpt = makeCpt();
    $grader = User::factory()->create();

    app(GradeCpt::class)->execute($cpt, false, $grader);

    expect($cpt->fresh()->passed)->toBeFalse();
    $this->assertDatabaseHas('cpts', ['id' => $cpt->id, 'passed' => false]);
});

test('GradeCpt fires CptGraded event', function () {
    $cpt = makeCpt();
    $grader = User::factory()->create();

    app(GradeCpt::class)->execute($cpt, true, $grader);

    Event::assertDispatched(CptGraded::class);
});

test('GradeCpt calls VatEud uploadCptLog when log is uploaded and logs exist', function () {
    Storage::fake('private');

    $examiner = User::factory()->create(['vatsim_id' => 1111111]);
    $trainee = User::factory()->create(['vatsim_id' => 2222222]);
    $grader = User::factory()->create();
    $course = Course::factory()->create(['solo_station' => 'EDDF_TWR']);

    $cpt = Cpt::create([
        'course_id' => $course->id,
        'trainee_id' => $trainee->id,
        'examiner_id' => $examiner->id,
        'local_id' => null,
        'date' => now()->addDays(7),
    ]);

    app(UploadCptLog::class)->execute($cpt, User::factory()->create(), UploadedFile::fake()->create('log.txt', 10));

    $vatEudMock = Mockery::mock(VatEudClientInterface::class);
    $vatEudMock->shouldReceive('uploadCptLog')->once()->andReturn(['success' => true]);
    $vatEudMock->shouldReceive('requestUpgrade')->once()->andReturn(['success' => true]);
    $this->app->instance(VatEudClientInterface::class, $vatEudMock);

    app(GradeCpt::class)->execute($cpt->fresh(), true, $grader);
});

test('GradeCpt does not call VatEud when log_uploaded is false', function () {
    $cpt = makeCpt();
    $grader = User::factory()->create();

    $vatEudMock = Mockery::mock(VatEudClientInterface::class);
    $vatEudMock->shouldNotReceive('uploadCptLog');
    $this->app->instance(VatEudClientInterface::class, $vatEudMock);

    app(GradeCpt::class)->execute($cpt, true, $grader);
});

test('GradeCpt does not call VatEud when no logs exist even if log_uploaded is true', function () {
    $cpt = makeCpt(['log_uploaded' => true]);
    $grader = User::factory()->create();

    $vatEudMock = Mockery::mock(VatEudClientInterface::class);
    $vatEudMock->shouldNotReceive('uploadCptLog');
    $this->app->instance(VatEudClientInterface::class, $vatEudMock);

    app(GradeCpt::class)->execute($cpt, true, $grader);
});

test('GradeCpt sets rating_upgrade_pending to true when upgrade is requested', function () {
    Storage::fake('private');

    $examiner = User::factory()->create(['vatsim_id' => 1111111]);
    $trainee = User::factory()->create(['vatsim_id' => 2222222, 'rating' => 3, 'rating_upgrade_pending' => false]);
    $grader = User::factory()->create();
    $course = Course::factory()->create(['solo_station' => 'EDDF_TWR']);

    $cpt = Cpt::create([
        'course_id' => $course->id,
        'trainee_id' => $trainee->id,
        'examiner_id' => $examiner->id,
        'local_id' => null,
        'date' => now()->addDays(7),
    ]);

    app(UploadCptLog::class)->execute($cpt, User::factory()->create(), UploadedFile::fake()->create('log.txt', 10));

    $vatEudMock = Mockery::mock(VatEudClientInterface::class);
    $vatEudMock->shouldReceive('uploadCptLog')->once()->andReturn(['success' => true]);
    $vatEudMock->shouldReceive('requestUpgrade')->once()->andReturn(['success' => true]);
    $this->app->instance(VatEudClientInterface::class, $vatEudMock);

    app(GradeCpt::class)->execute($cpt->fresh(), true, $grader);

    expect($trainee->fresh()->rating_upgrade_pending)->toBeTrue();
});

test('GradeCpt does not request upgrade when cpt is not passed', function () {
    Storage::fake('private');

    $examiner = User::factory()->create(['vatsim_id' => 1111111]);
    $trainee = User::factory()->create(['vatsim_id' => 2222222]);
    $grader = User::factory()->create();
    $course = Course::factory()->create(['solo_station' => 'EDDF_TWR']);

    $cpt = Cpt::create([
        'course_id' => $course->id,
        'trainee_id' => $trainee->id,
        'examiner_id' => $examiner->id,
        'local_id' => null,
        'date' => now()->addDays(7),
    ]);

    app(UploadCptLog::class)->execute($cpt, User::factory()->create(), UploadedFile::fake()->create('log.txt', 10));

    $vatEudMock = Mockery::mock(VatEudClientInterface::class);
    $vatEudMock->shouldReceive('uploadCptLog')->once()->andReturn(['success' => true]);
    $vatEudMock->shouldNotReceive('requestUpgrade');
    $this->app->instance(VatEudClientInterface::class, $vatEudMock);

    app(GradeCpt::class)->execute($cpt->fresh(), false, $grader);
});
