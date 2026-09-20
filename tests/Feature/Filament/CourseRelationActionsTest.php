<?php

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Models\ChiefOfTraining;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('mentors can be added and removed from a course, but not below one', function () {
    $course = Course::factory()->create();
    $mentorA = User::factory()->create();
    $mentorB = User::factory()->create();

    $component = Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()]);

    $component->call('addMentor', $mentorA->id);
    expect($course->mentors()->pluck('users.id')->all())->toBe([$mentorA->id]);

    $component->call('addMentor', $mentorB->id);
    expect($course->mentors()->count())->toBe(2);

    $component->call('removeMentor', $mentorA->id);
    expect($course->mentors()->pluck('users.id')->all())->toBe([$mentorB->id]);

    // Cannot remove the last mentor.
    $component->call('removeMentor', $mentorB->id);
    expect($course->mentors()->count())->toBe(1);
});

test('trainees can be added and removed from a course', function () {
    $course = Course::factory()->create();
    $trainee = User::factory()->create();

    $component = Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()]);

    $component->call('addTrainee', $trainee->id);
    expect($course->allTrainees()->pluck('users.id')->all())->toBe([$trainee->id]);

    $component->call('removeTrainee', $trainee->id);
    expect($course->allTrainees()->count())->toBe(0);
});

test('trainee pivot data can be edited from the course page', function () {
    $course = Course::factory()->create();
    $trainee = User::factory()->create();
    $mentor = User::factory()->create();
    $course->allTrainees()->attach($trainee->id);

    $component = Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()]);

    $component->call('updateTraineePivot', $trainee->id, [
        'claimed_by_mentor_id' => $mentor->id,
        'claimed_at' => now()->toDateTimeString(),
        'completed_at' => null,
        'remarks' => 'Doing well',
    ]);

    $pivot = $course->allTrainees()->find($trainee->id)->pivot;
    expect($pivot->claimed_by_mentor_id)->toBe($mentor->id)
        ->and($pivot->remarks)->toBe('Doing well');
});

test('chief of training can be added and removed from a course', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $component = Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()]);

    $component->call('addChiefOfTraining', $user->id);

    $cot = ChiefOfTraining::where('course_id', $course->id)->first();
    expect($cot)->not->toBeNull()
        ->and($cot->user_id)->toBe($user->id);

    $component->call('removeChiefOfTraining', $cot->id);
    expect(ChiefOfTraining::where('course_id', $course->id)->exists())->toBeFalse();
});
