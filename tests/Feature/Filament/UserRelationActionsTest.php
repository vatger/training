<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\ChiefOfTraining;
use App\Models\Course;
use App\Models\Familiarisation;
use App\Models\FamiliarisationSector;
use App\Models\LeadingMentor;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('course enrollments can be added and removed from the user page', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    $component = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()]);

    $component->call('addCourseEnrollment', $course->id);
    expect($user->activeCourses()->pluck('courses.id')->all())->toBe([$course->id]);

    $component->call('removeCourseEnrollment', $course->id);
    expect($user->activeCourses()->count())->toBe(0);
});

test('waiting list entries can be added, edited, and removed from the user page', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    $component = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()]);

    $component->call('addWaitingListEntry', $course->id);
    $entry = WaitingListEntry::where('user_id', $user->id)->first();
    expect($entry)->not->toBeNull();

    $component->call('updateWaitingListEntry', $entry->id, ['activity' => 12.5, 'remarks' => 'Very active']);
    expect($entry->refresh()->activity)->toBe(12.5)
        ->and($entry->remarks)->toBe('Very active');

    $component->call('removeWaitingListEntry', $entry->id);
    expect(WaitingListEntry::where('user_id', $user->id)->exists())->toBeFalse();
});

test('course enrollment pivot data can be edited from the user page', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $mentor = User::factory()->create();
    $user->activeCourses()->attach($course->id);

    $component = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()]);

    $component->call('updateCourseEnrollmentPivot', $course->id, [
        'claimed_by_mentor_id' => $mentor->id,
        'claimed_at' => now()->toDateTimeString(),
        'completed_at' => null,
        'remarks' => 'Progressing well',
    ]);

    $pivot = $user->activeCourses()->find($course->id)->pivot;
    expect($pivot->claimed_by_mentor_id)->toBe($mentor->id)
        ->and($pivot->remarks)->toBe('Progressing well');
});

test('familiarisations can be added and removed from the user page', function () {
    $user = User::factory()->create();
    $sector = FamiliarisationSector::create(['name' => 'KOLN', 'fir' => 'EDGG']);

    $component = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()]);

    $component->call('addFamiliarisation', $sector->id);
    $fam = Familiarisation::where('user_id', $user->id)->first();
    expect($fam)->not->toBeNull();

    $component->call('removeFamiliarisation', $fam->id);
    expect(Familiarisation::where('user_id', $user->id)->exists())->toBeFalse();
});

test('chief of training courses can be added and removed from the user page', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    $component = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()]);

    $component->call('addChiefOfTrainingCourse', $course->id);
    $cot = ChiefOfTraining::where('user_id', $user->id)->first();
    expect($cot)->not->toBeNull()
        ->and($cot->course_id)->toBe($course->id);

    $component->call('removeChiefOfTrainingCourse', $cot->id);
    expect(ChiefOfTraining::where('user_id', $user->id)->exists())->toBeFalse();
});

test('leading mentor firs can be added and removed from the user page', function () {
    $user = User::factory()->create();

    $component = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()]);

    $component->call('addLeadingMentorFir', 'EDGG');
    $lm = LeadingMentor::where('user_id', $user->id)->first();
    expect($lm)->not->toBeNull()
        ->and($lm->fir)->toBe('EDGG');

    $component->call('removeLeadingMentorFir', $lm->id);
    expect(LeadingMentor::where('user_id', $user->id)->exists())->toBeFalse();
});
