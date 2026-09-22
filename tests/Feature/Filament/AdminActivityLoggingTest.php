<?php

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\Examiners\Pages\CreateExaminer;
use App\Filament\Resources\Examiners\Pages\EditExaminer;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Examiner;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_superuser' => true]);
    $this->actingAs($this->admin);
});

/**
 * Livewire::test() instantiates components in-process and skips the real HTTP
 * kernel/middleware stack, so Filament's own "are we inside a panel request"
 * state (which the admin activity log relies on to scope itself to admin-panel
 * actions) never gets set automatically. Call this to simulate what the real
 * middleware (SetUpPanel, DispatchServingFilamentEvent) does for every request
 * in production, including Livewire's AJAX updates.
 */
function simulateBeingInsideTheAdminPanel(): void
{
    Filament::setCurrentPanel('admin');
    Filament::setServingStatus();
}

test('creating a resource through the admin panel is logged', function () {
    simulateBeingInsideTheAdminPanel();
    $trainee = User::factory()->create();

    Livewire::test(CreateExaminer::class)
        ->fillForm([
            'user_id' => $trainee->id,
            'callsign' => 'ATDGERTST',
            'positions' => [Examiner::POSITION_TWR],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $log = ActivityLog::where('action', 'examiner.created')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($this->admin->id)
        ->and($log->model_type)->toBe(Examiner::class)
        ->and($log->is_admin_action)->toBeTrue();
});

test('editing a resource through the admin panel is logged with the changed attributes', function () {
    simulateBeingInsideTheAdminPanel();
    $trainee = User::factory()->create();
    $examiner = Examiner::create([
        'user_id' => $trainee->id,
        'callsign' => 'ATDGERTST',
        'positions' => [Examiner::POSITION_TWR],
    ]);

    Livewire::test(EditExaminer::class, ['record' => $examiner->getRouteKey()])
        ->fillForm(['callsign' => 'ATDGERNEW'])
        ->call('save')
        ->assertHasNoFormErrors();

    $log = ActivityLog::where('action', 'examiner.updated')->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['changes']['callsign']['new'])->toBe('ATDGERNEW');
});

test('changes made outside the admin panel are not written to the admin activity log', function () {
    $trainee = User::factory()->create();

    // Created directly, bypassing Filament entirely — e.g. a console command or the main app.
    Examiner::create([
        'user_id' => $trainee->id,
        'callsign' => 'ATDGERTST',
        'positions' => [Examiner::POSITION_TWR],
    ]);

    expect(ActivityLog::where('action', 'examiner.created')->exists())->toBeFalse();
});

test('role permission changes made through the admin panel are logged', function () {
    $permission = Permission::create(['name' => 'admin.users.view']);
    $role = Role::create(['name' => 'Test Role']);

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['permissions' => [$permission->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    $log = ActivityLog::where('action', 'role.permissions_updated')->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['added'])->toBe([$permission->name])
        ->and($log->is_admin_action)->toBeTrue();
});

test('assigning a role to a user through the admin panel is logged', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'EDGG Mentor']);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['roles' => [$role->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    $log = ActivityLog::where('action', 'user.roles_updated')->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['added'])->toBe([$role->name])
        ->and($log->model_id)->toBe($user->id);
});

test('enrolling a user into a course from the admin panel is logged', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->call('addCourseEnrollment', $course->id);

    $log = ActivityLog::where('action', 'user.course_enrollment_added')->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['course_id'])->toBe($course->id)
        ->and($log->model_id)->toBe($user->id)
        ->and($log->is_admin_action)->toBeTrue();
});

test('an admin-panel change logged this way is hidden from a superuser but visible to an admin', function () {
    simulateBeingInsideTheAdminPanel();
    $trainee = User::factory()->create();

    Livewire::test(CreateExaminer::class)
        ->fillForm([
            'user_id' => $trainee->id,
            'callsign' => 'ATDGERTST',
            'positions' => [Examiner::POSITION_TWR],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $log = ActivityLog::where('action', 'examiner.created')->firstOrFail();

    // Same admin (who is only a superuser here, not an admin) viewing the list.
    Livewire::test(ListActivityLogs::class)
        ->assertCanNotSeeTableRecords([$log]);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(ListActivityLogs::class)
        ->assertCanSeeTableRecords([$log]);
});
