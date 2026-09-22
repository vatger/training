<?php

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\ApiKeys\ApiKeyResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Cpts\CptResource;
use App\Filament\Resources\EndorsementActivities\EndorsementActivityResource;
use App\Filament\Resources\Examiners\ExaminerResource;
use App\Filament\Resources\Familiarisations\FamiliarisationResource;
use App\Filament\Resources\FamiliarisationSectors\FamiliarisationSectorResource;
use App\Filament\Resources\LeadingMentors\LeadingMentorResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Tier2Endorsements\Tier2EndorsementResource;
use App\Filament\Resources\TrainingLogs\TrainingLogResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\WaitingListRestrictions\WaitingListRestrictionResource;
use App\Filament\Resources\WaitingLists\WaitingListResource;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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

test('a superuser only sees non-admin-panel log entries in the activity log', function () {
    $adminPanelLog = ActivityLog::create([
        'action' => 'examiner.created',
        'description' => 'Admin created an examiner',
        'is_admin_action' => true,
    ]);

    $domainEventLog = ActivityLog::create([
        'action' => 'solo.granted',
        'description' => 'Mentor granted a solo endorsement',
        'is_admin_action' => false,
    ]);

    Livewire::test(ListActivityLogs::class)
        ->assertCanSeeTableRecords([$domainEventLog])
        ->assertCanNotSeeTableRecords([$adminPanelLog]);
});

test('an admin sees every log entry, including admin-panel changes', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $adminPanelLog = ActivityLog::create([
        'action' => 'examiner.created',
        'description' => 'Admin created an examiner',
        'is_admin_action' => true,
    ]);

    $domainEventLog = ActivityLog::create([
        'action' => 'solo.granted',
        'description' => 'Mentor granted a solo endorsement',
        'is_admin_action' => false,
    ]);

    Livewire::test(ListActivityLogs::class)
        ->assertCanSeeTableRecords([$domainEventLog, $adminPanelLog]);
});

test('a superuser cannot directly open an admin-panel change log entry by URL', function () {
    $adminPanelLog = ActivityLog::create([
        'action' => 'examiner.created',
        'description' => 'Admin created an examiner',
        'is_admin_action' => true,
    ]);

    // The resource's own query already scopes the record out for a superuser,
    // so route model binding can't even resolve it — a 404, not a 403.
    $this->get(ActivityLogResource::getUrl('view', ['record' => $adminPanelLog]))->assertNotFound();
});

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

test('familiarisation cluster redirects to its first sub-resource', function () {
    $this->get('/admin/familiarisation')->assertRedirect();
});

test('familiarisation sectors sub-resource inside the cluster renders', function () {
    $this->get('/admin/familiarisation/familiarisation-sectors')->assertSuccessful();
});

test('familiarisations sub-resource inside the cluster renders', function () {
    $this->get('/admin/familiarisation/familiarisations')->assertSuccessful();
});

test('endorsements cluster redirects to its first sub-resource', function () {
    $this->get('/admin/endorsements')->assertRedirect();
});

test('tier 1 endorsement activities sub-resource inside the cluster renders', function () {
    $this->get('/admin/endorsements/endorsement-activities')->assertSuccessful();
});

test('tier 2 endorsements sub-resource inside the cluster renders', function () {
    $this->get('/admin/endorsements/tier2-endorsements')->assertSuccessful();
});

test('permissions resource has been removed from the admin panel', function () {
    $this->get('/admin/permissions')->assertNotFound();
});
