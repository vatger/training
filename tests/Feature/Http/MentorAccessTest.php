<?php

use App\Models\Course;
use App\Models\LeadingMentor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Http::fake();
});

function accessPersona(string $persona): User
{
    $user = User::factory()->create(['subdivision' => 'GER', 'rating' => 5]);

    switch ($persona) {
        case 'EDGG Mentor':
        case 'ATD Leitung':
        case 'VATGER Leitung':
            $user->roles()->attach(Role::firstOrCreate(['name' => $persona]));
            break;
        case 'is_superuser':
            $user->update(['is_superuser' => true]);
            break;
        case 'is_admin only':
            $user->update(['is_admin' => true]);
            break;
        case 'is_staff only':
            $user->update(['is_staff' => true]);
            break;
        case 'chief of training':
            DB::table('chief_of_trainings')->insert([
                'user_id' => $user->id,
                'course_id' => Course::factory()->create()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            break;
        case 'leading mentor':
            LeadingMentor::create(['user_id' => $user->id, 'fir' => 'EDGG']);
            break;
    }

    return $user->fresh();
}

// Who passes the `mentor` ability: mentors, leadership, chiefs of training, leading mentors, superusers and admins.
dataset('mentor ability', [
    'plain user' => ['plain user', false],
    'EDGG Mentor' => ['EDGG Mentor', true],
    'ATD Leitung' => ['ATD Leitung', true],
    'VATGER Leitung' => ['VATGER Leitung', true],
    'is_superuser' => ['is_superuser', true],
    'is_admin only' => ['is_admin only', true],
    'is_staff only' => ['is_staff only', false],
    'chief of training' => ['chief of training', true],
    'leading mentor' => ['leading mentor', true],
]);

test('mentor ability', function (string $persona, bool $allowed) {
    expect(Gate::forUser(accessPersona($persona))->allows('mentor'))->toBe($allowed);
})->with('mentor ability');

test('guests do not pass the mentor ability', function () {
    expect(Gate::allows('mentor'))->toBeFalse();
});

test('controller checks follow the mentor ability', function (string $persona, bool $allowed) {
    $user = accessPersona($persona);

    // Redirect-back style (TraineeManagementController): the route middleware answers with a 403,
    // the in-controller check with an error.
    $redirect = $this->actingAs($user)->post('/overview/claim-trainee');
    $deniedRedirect = $redirect->status() === 403 || collect(session('errors')?->all())->contains('Access denied');
    expect($deniedRedirect)->toBe(! $allowed);

    // JSON style (SoloController)
    $json = $this->actingAs($user)->postJson('/overview/solo/requirements');
    expect($json->status() === 403)->toBe(! $allowed);
})->with('mentor ability');

test('endorsement management follows the mentor ability', function (string $persona, bool $allowed) {
    $this->actingAs(accessPersona($persona))
        ->get('/endorsements/manage')
        ->assertStatus($allowed ? 200 : 403);
})->with('mentor ability');
