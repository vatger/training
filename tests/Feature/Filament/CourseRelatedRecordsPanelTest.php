<?php

use App\Filament\Resources\Courses\CourseResource;
use App\Models\ChiefOfTraining;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('course edit page shows read-only panels for mentors, trainees, and chief of training', function () {
    $course = Course::factory()->create(['name' => 'EDDF_TWR']);

    $mentor = User::factory()->create(['first_name' => 'Mentor', 'last_name' => 'One', 'vatsim_id' => 1234567]);
    $course->mentors()->attach($mentor->id);

    $trainee = User::factory()->create(['first_name' => 'Trainee', 'last_name' => 'One']);
    $claimer = User::factory()->create(['first_name' => 'Claimer', 'last_name' => 'Mentor']);
    $course->allTrainees()->attach($trainee->id, [
        'claimed_by_mentor_id' => $claimer->id,
        'claimed_at' => now(),
    ]);

    $cot = User::factory()->create(['first_name' => 'Chief', 'last_name' => 'Trainer']);
    ChiefOfTraining::create(['user_id' => $cot->id, 'course_id' => $course->id]);

    $response = $this->get(CourseResource::getUrl('edit', ['record' => $course]));

    $response->assertSuccessful();
    $response->assertSee('Mentor One');
    $response->assertSee('1234567');
    $response->assertSee('Trainee One');
    $response->assertSee('Claimer Mentor');
    $response->assertSee('Chief Trainer');
});
