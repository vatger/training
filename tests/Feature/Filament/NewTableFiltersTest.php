<?php

use App\Filament\Resources\FamiliarisationSectors\Pages\ListFamiliarisationSectors;
use App\Filament\Resources\Tier2Endorsements\Pages\ListTier2Endorsements;
use App\Filament\Resources\WaitingListRestrictions\Pages\ListWaitingListRestrictions;
use App\Models\FamiliarisationSector;
use App\Models\Tier2Endorsement;
use App\Models\User;
use App\Models\WaitingListRestriction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('tier2 endorsements can be filtered by position', function () {
    $twr = Tier2Endorsement::create(['name' => 'Tower Mentor', 'position' => 'TWR', 'moodle_course_id' => 1]);
    $ctr = Tier2Endorsement::create(['name' => 'Centre Mentor', 'position' => 'CTR', 'moodle_course_id' => 2]);

    Livewire::test(ListTier2Endorsements::class)
        ->filterTable('position', ['TWR'])
        ->assertCanSeeTableRecords([$twr])
        ->assertCanNotSeeTableRecords([$ctr]);
});

test('familiarisation sectors can be filtered by FIR', function () {
    $edgg = FamiliarisationSector::create(['name' => 'ABC', 'fir' => 'EDGG']);
    $edmm = FamiliarisationSector::create(['name' => 'XYZ', 'fir' => 'EDMM']);

    Livewire::test(ListFamiliarisationSectors::class)
        ->filterTable('fir', ['EDGG'])
        ->assertCanSeeTableRecords([$edgg])
        ->assertCanNotSeeTableRecords([$edmm]);
});

test('waiting list restrictions can be filtered by type and active/expired', function () {
    $activeRestriction = WaitingListRestriction::create([
        'user_id' => User::factory()->create()->id,
        'type' => 'RTG',
        'expires_at' => null,
    ]);

    $expiredRestriction = WaitingListRestriction::create([
        'user_id' => User::factory()->create()->id,
        'type' => 'EDMT',
        'expires_at' => now()->subDay(),
    ]);

    Livewire::test(ListWaitingListRestrictions::class)
        ->filterTable('type', ['RTG'])
        ->assertCanSeeTableRecords([$activeRestriction])
        ->assertCanNotSeeTableRecords([$expiredRestriction])
        ->resetTableFilters()
        ->filterTable('active', true)
        ->assertCanSeeTableRecords([$activeRestriction])
        ->assertCanNotSeeTableRecords([$expiredRestriction])
        ->resetTableFilters()
        ->filterTable('expired', true)
        ->assertCanSeeTableRecords([$expiredRestriction])
        ->assertCanNotSeeTableRecords([$activeRestriction]);
});
