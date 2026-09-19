<?php

use App\Filament\Resources\TrainingLogs\Pages\ListTrainingLogs;
use App\Models\Course;
use App\Models\TrainingLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

function makeTrainingLog(array $overrides = []): TrainingLog
{
    return TrainingLog::create(array_merge([
        'trainee_id' => User::factory()->create()->id,
        'session_date' => now(),
        'position' => 'EDDF_TWR',
        'type' => 'O',
        'theory' => 3,
        'phraseology' => 3,
        'coordination' => 3,
        'tag_management' => 3,
        'situational_awareness' => 3,
        'problem_recognition' => 3,
        'traffic_planning' => 3,
        'reaction' => 3,
        'separation' => 3,
        'efficiency' => 3,
        'ability_to_work_under_pressure' => 3,
        'motivation' => 3,
        'result' => true,
    ], $overrides));
}

test('training logs can be filtered by course via the course_id filter', function () {
    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();

    $logA = makeTrainingLog(['course_id' => $courseA->id]);
    $logB = makeTrainingLog(['course_id' => $courseB->id]);

    Livewire::test(ListTrainingLogs::class)
        ->filterTable('course_id', $courseA->id)
        ->assertCanSeeTableRecords([$logA])
        ->assertCanNotSeeTableRecords([$logB]);
});

test('training logs can be filtered to a recent window', function () {
    $recent = makeTrainingLog(['session_date' => now()->subDays(2)]);
    $old = makeTrainingLog(['session_date' => now()->subDays(60)]);

    Livewire::test(ListTrainingLogs::class)
        ->filterTable('recent', ['days' => 7])
        ->assertCanSeeTableRecords([$recent])
        ->assertCanNotSeeTableRecords([$old]);
});

test('mentor and course filters combine to show a mentor\'s recent sessions in one course', function () {
    $mentor = User::factory()->create();
    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();

    $match = makeTrainingLog(['mentor_id' => $mentor->id, 'course_id' => $courseA->id]);
    makeTrainingLog(['mentor_id' => $mentor->id, 'course_id' => $courseB->id]);
    makeTrainingLog(['course_id' => $courseA->id]);

    Livewire::test(ListTrainingLogs::class)
        ->filterTable('mentor_id', [$mentor->id])
        ->filterTable('course_id', $courseA->id)
        ->assertCanSeeTableRecords([$match])
        ->assertCountTableRecords(1);
});
