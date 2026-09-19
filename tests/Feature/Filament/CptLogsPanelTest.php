<?php

use App\Filament\Resources\Cpts\CptResource;
use App\Filament\Resources\Cpts\Pages\ListCpts;
use App\Filament\Resources\Cpts\Pages\ViewCpt;
use App\Models\Cpt;
use App\Models\CptLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
    Storage::fake('private');
});

test('cpt view page shows a read-only panel of its logs', function () {
    $trainee = User::factory()->create();
    $uploader = User::factory()->create(['first_name' => 'Up', 'last_name' => 'Loader']);
    $cpt = Cpt::create(['trainee_id' => $trainee->id, 'date' => now(), 'log_uploaded' => true]);

    Storage::disk('private')->put('cpt-logs/test.pdf', 'contents');

    CptLog::create([
        'cpt_id' => $cpt->id,
        'uploaded_by_id' => $uploader->id,
        'log_file' => 'cpt-logs/test.pdf',
    ]);

    $response = $this->get(CptResource::getUrl('view', ['record' => $cpt]));

    $response->assertSuccessful();
    $response->assertSee('test.pdf');
    $response->assertSee('Up Loader');
});

test('delete_log action removes the file, the record, and resets log_uploaded when it was the last log', function () {
    $trainee = User::factory()->create();
    $uploader = User::factory()->create();
    $cpt = Cpt::create(['trainee_id' => $trainee->id, 'date' => now(), 'log_uploaded' => true]);

    Storage::disk('private')->put('cpt-logs/only.pdf', 'contents');

    $log = CptLog::create([
        'cpt_id' => $cpt->id,
        'uploaded_by_id' => $uploader->id,
        'log_file' => 'cpt-logs/only.pdf',
    ]);

    Livewire::test(ViewCpt::class, ['record' => $cpt->getRouteKey()])
        ->callAction('delete_log', data: ['log_id' => $log->id]);

    Storage::disk('private')->assertMissing('cpt-logs/only.pdf');
    expect(CptLog::find($log->id))->toBeNull();
    expect($cpt->refresh()->log_uploaded)->toBeFalse();
});

test('cpt view page links the log file name to the log-viewing route', function () {
    $trainee = User::factory()->create();
    $uploader = User::factory()->create();
    $cpt = Cpt::create(['trainee_id' => $trainee->id, 'date' => now()]);

    Storage::disk('private')->put('cpt-logs/open-me.pdf', 'contents');

    $log = CptLog::create([
        'cpt_id' => $cpt->id,
        'uploaded_by_id' => $uploader->id,
        'log_file' => 'cpt-logs/open-me.pdf',
    ]);

    $response = $this->get(CptResource::getUrl('view', ['record' => $cpt]));

    $response->assertSuccessful();
    $response->assertSee(route('cpt.log.view', $log->id), escape: false);
});

test('deleting a CPT also deletes its log files from storage', function () {
    $trainee = User::factory()->create();
    $uploader = User::factory()->create();
    $cpt = Cpt::create(['trainee_id' => $trainee->id, 'date' => now(), 'log_uploaded' => true]);

    Storage::disk('private')->put('cpt-logs/a.pdf', 'contents');
    Storage::disk('private')->put('cpt-logs/b.pdf', 'contents');

    CptLog::create(['cpt_id' => $cpt->id, 'uploaded_by_id' => $uploader->id, 'log_file' => 'cpt-logs/a.pdf']);
    CptLog::create(['cpt_id' => $cpt->id, 'uploaded_by_id' => $uploader->id, 'log_file' => 'cpt-logs/b.pdf']);

    $cpt->delete();

    Storage::disk('private')->assertMissing('cpt-logs/a.pdf');
    Storage::disk('private')->assertMissing('cpt-logs/b.pdf');
    expect(CptLog::count())->toBe(0);
    expect(Cpt::count())->toBe(0);
});

test('cpt can be deleted from the table and from the edit page', function () {
    $trainee = User::factory()->create();
    $cpt = Cpt::create(['trainee_id' => $trainee->id, 'date' => now()]);

    Livewire::test(ListCpts::class)
        ->callTableAction('delete', $cpt);

    expect(Cpt::count())->toBe(0);
});

test('delete_log does not reset log_uploaded when other logs remain', function () {
    $trainee = User::factory()->create();
    $uploader = User::factory()->create();
    $cpt = Cpt::create(['trainee_id' => $trainee->id, 'date' => now(), 'log_uploaded' => true]);

    Storage::disk('private')->put('cpt-logs/one.pdf', 'contents');
    Storage::disk('private')->put('cpt-logs/two.pdf', 'contents');

    $logOne = CptLog::create(['cpt_id' => $cpt->id, 'uploaded_by_id' => $uploader->id, 'log_file' => 'cpt-logs/one.pdf']);
    CptLog::create(['cpt_id' => $cpt->id, 'uploaded_by_id' => $uploader->id, 'log_file' => 'cpt-logs/two.pdf']);

    Livewire::test(ViewCpt::class, ['record' => $cpt->getRouteKey()])
        ->callAction('delete_log', data: ['log_id' => $logOne->id]);

    Storage::disk('private')->assertMissing('cpt-logs/one.pdf');
    Storage::disk('private')->assertExists('cpt-logs/two.pdf');
    expect($cpt->refresh()->log_uploaded)->toBeTrue();
});
