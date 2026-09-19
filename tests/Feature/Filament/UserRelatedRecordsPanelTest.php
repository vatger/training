<?php

use App\Filament\Resources\Users\UserResource;
use App\Models\ChiefOfTraining;
use App\Models\Course;
use App\Models\Cpt;
use App\Models\LeadingMentor;
use App\Models\TrainingLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('user edit page shows a read-only panel of their CPTs with no edit/attach actions', function () {
    $trainee = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $examiner = User::factory()->create(['first_name' => 'John', 'last_name' => 'Examiner']);
    $course = Course::factory()->create(['name' => 'EDDF_TWR']);

    Cpt::create([
        'trainee_id' => $trainee->id,
        'examiner_id' => $examiner->id,
        'course_id' => $course->id,
        'date' => now(),
        'passed' => true,
    ]);

    $response = $this->get(UserResource::getUrl('edit', ['record' => $trainee]));

    $response->assertSuccessful();
    $response->assertSee('EDDF_TWR');
    $response->assertSee('John Examiner');
    $response->assertSee('Passed');
});

test('user edit page shows read-only panels for course enrollment, training logs, and CoT/LM summaries', function () {
    $trainee = User::factory()->create();
    $mentor = User::factory()->create(['first_name' => 'Mentor', 'last_name' => 'Person']);
    $course = Course::factory()->create(['name' => 'EDDM_APP']);

    $course->activeTrainees()->attach($trainee->id, [
        'claimed_by_mentor_id' => $mentor->id,
        'claimed_at' => now(),
        'remarks' => 'Doing great',
    ]);

    TrainingLog::create([
        'trainee_id' => $trainee->id,
        'mentor_id' => $mentor->id,
        'course_id' => $course->id,
        'session_date' => now(),
        'position' => 'EDDM_APP',
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
    ]);

    $cotCourse = Course::factory()->create(['name' => 'EDDS_TWR']);
    ChiefOfTraining::create(['user_id' => $trainee->id, 'course_id' => $cotCourse->id]);
    LeadingMentor::create(['user_id' => $trainee->id, 'fir' => 'EDGG']);

    $response = $this->get(UserResource::getUrl('edit', ['record' => $trainee]));

    $response->assertSuccessful();
    $response->assertSee('EDDM_APP');
    $response->assertSee('Mentor Person');
    $response->assertSee('Doing great');
    $response->assertSee('EDDS_TWR');
    $response->assertSee('EDGG');
});
