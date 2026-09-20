<?php

use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

function baseCourseData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Course',
        'trainee_display_name' => 'Test Course',
        'airport_name' => 'Test Airport',
        'airport_icao' => 'EDDT',
        'min_rating' => 2,
        'max_rating' => 3,
    ], $overrides);
}

test('familiarisation sector field only shows for FAM courses', function () {
    $component = Livewire::test(CreateCourse::class)
        ->fillForm(baseCourseData(['type' => 'RTG', 'position' => 'TWR']));

    $component->assertFormFieldIsHidden('familiarisation_sector_id');

    $component->fillForm(baseCourseData(['type' => 'FAM', 'position' => 'TWR']));
    $component->assertFormFieldIsVisible('familiarisation_sector_id');
});

test('required familiarisations field only shows for EDMT + CTR courses', function () {
    $component = Livewire::test(CreateCourse::class)
        ->fillForm(baseCourseData(['type' => 'EDMT', 'position' => 'TWR']));

    $component->assertFormFieldIsHidden('requiredFamiliarisationSectors');

    $component->fillForm(baseCourseData(['type' => 'EDMT', 'position' => 'CTR']));
    $component->assertFormFieldIsVisible('requiredFamiliarisationSectors');

    $component->fillForm(baseCourseData(['type' => 'RTG', 'position' => 'CTR']));
    $component->assertFormFieldIsHidden('requiredFamiliarisationSectors');
});

test('endorsement groups field only shows for EDMT courses', function () {
    $component = Livewire::test(CreateCourse::class)
        ->fillForm(baseCourseData(['type' => 'RTG', 'position' => 'TWR']));

    $component->assertFormFieldIsHidden('endorsement_groups');

    $component->fillForm(baseCourseData(['type' => 'EDMT', 'position' => 'TWR']));
    $component->assertFormFieldIsVisible('endorsement_groups');
});
