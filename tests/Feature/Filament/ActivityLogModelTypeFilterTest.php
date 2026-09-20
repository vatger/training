<?php

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('model_type filter includes Cpt, which the app actually logs against', function () {
    $cptLog = ActivityLog::create([
        'action' => 'cpt.created',
        'model_type' => 'App\Models\Cpt',
        'model_id' => 1,
        'description' => 'CPT created',
    ]);

    $courseLog = ActivityLog::create([
        'action' => 'course.mentor_added',
        'model_type' => 'App\Models\Course',
        'model_id' => 1,
        'description' => 'Mentor added',
    ]);

    Livewire::test(ListActivityLogs::class)
        ->filterTable('model_type', ['App\Models\Cpt'])
        ->assertCanSeeTableRecords([$cptLog])
        ->assertCanNotSeeTableRecords([$courseLog]);
});
