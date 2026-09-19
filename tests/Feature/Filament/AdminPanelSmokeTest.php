<?php

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\ApiKeys\ApiKeyResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Cpts\CptResource;
use App\Filament\Resources\EndorsementActivities\EndorsementActivityResource;
use App\Filament\Resources\Examiners\ExaminerResource;
use App\Filament\Resources\Familiarisations\FamiliarisationResource;
use App\Filament\Resources\FamiliarisationSectors\FamiliarisationSectorResource;
use App\Filament\Resources\LeadingMentors\LeadingMentorResource;
use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Tier2Endorsements\Tier2EndorsementResource;
use App\Filament\Resources\TrainingLogs\TrainingLogResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\WaitingListRestrictions\WaitingListRestrictionResource;
use App\Filament\Resources\WaitingLists\WaitingListResource;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

$resources = [
    ActivityLogResource::class,
    ApiKeyResource::class,
    CourseResource::class,
    CptResource::class,
    EndorsementActivityResource::class,
    ExaminerResource::class,
    FamiliarisationResource::class,
    FamiliarisationSectorResource::class,
    LeadingMentorResource::class,
    PermissionResource::class,
    RoleResource::class,
    Tier2EndorsementResource::class,
    TrainingLogResource::class,
    UserResource::class,
    WaitingListRestrictionResource::class,
    WaitingListResource::class,
];

foreach ($resources as $resourceClass) {
    test("{$resourceClass} index page renders", function () use ($resourceClass) {
        $this->get($resourceClass::getUrl('index'))->assertSuccessful();
    });
}

test('dashboard renders', function () {
    $this->get('/admin')->assertSuccessful();
});

test('user edit page renders', function () {
    $user = User::factory()->create();

    $this->get(UserResource::getUrl('edit', ['record' => $user]))->assertSuccessful();
});

test('course edit page renders', function () {
    $course = Course::factory()->create();

    $this->get(CourseResource::getUrl('edit', ['record' => $course]))->assertSuccessful();
});
