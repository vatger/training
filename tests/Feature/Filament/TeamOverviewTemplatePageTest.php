<?php

use App\Filament\Pages\TeamOverviewTemplatePage;
use App\Models\TeamOverviewTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('superusers cannot access the template page', function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));

    expect(TeamOverviewTemplatePage::canAccess())->toBeFalse();
    $this->get(TeamOverviewTemplatePage::getUrl())->assertForbidden();
});

test('admins can save the template', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $content = "Edited\n".implode("\n", TeamOverviewTemplate::PLACEHOLDERS);

    Livewire::test(TeamOverviewTemplatePage::class)
        ->fillForm(['content' => $content])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(TeamOverviewTemplate::currentContent())->toBe($content);
});

test('saving requires every placeholder', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(TeamOverviewTemplatePage::class)
        ->fillForm(['content' => 'no placeholders'])
        ->call('save')
        ->assertHasFormErrors(['content']);

    expect(TeamOverviewTemplate::count())->toBe(0);
});
