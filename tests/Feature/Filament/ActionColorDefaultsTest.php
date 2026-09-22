<?php

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Models\Course;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('a plain action defaults to the info color panel-wide, without setting it explicitly', function () {
    $action = Action::make('some_action');

    expect($action->getColor())->toBe('info');
});

test('DeleteAction keeps its own danger color despite the panel-wide info default', function () {
    $action = DeleteAction::make();

    expect($action->getColor())->toBe('danger');
});

test('DeleteBulkAction keeps its own danger color despite the panel-wide info default', function () {
    $action = DeleteBulkAction::make();

    expect($action->getColor())->toBe('danger');
});

test('CreateAction picks up the panel-wide info default', function () {
    $action = CreateAction::make();

    expect($action->getColor())->toBe('info');
});

test('EditAction is overridden to info instead of falling back to the brand-red primary color', function () {
    // EditAction sets its own ->defaultColor('primary') in its setUp(), which — because
    // subclass setUp() runs after Action::configureUsing() in Filament's configuration
    // order — would otherwise silently override the panel-wide info default back to
    // 'primary' (this panel's brand color, which is red). AdminPanelProvider registers a
    // second, EditAction-specific override to fix this; this test guards against it
    // regressing back to a red "Edit" button.
    $action = EditAction::make();

    expect($action->getColor())->toBe('info');
});

test('an explicit color set on an action still wins over the panel-wide default', function () {
    $action = Action::make('some_action')->color('warning');

    expect($action->getColor())->toBe('warning');
});

test('course edit page mentors "Add" action has no explicit color and relies on the panel default', function () {
    $course = Course::factory()->create();

    $component = Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()]);

    // This just proves the page/action tree still renders correctly now that the
    // explicit ->color('info') calls were removed in favour of the global default.
    $component->assertSuccessful();
});
