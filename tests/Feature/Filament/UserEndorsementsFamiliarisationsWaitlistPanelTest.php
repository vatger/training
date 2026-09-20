<?php

use App\Filament\Resources\Users\UserResource;
use App\Models\Course;
use App\Models\EndorsementActivity;
use App\Models\Familiarisation;
use App\Models\FamiliarisationSector;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('user edit page shows waiting list, endorsements, and familiarisations panels', function () {
    $user = User::factory()->create(['vatsim_id' => 1122334]);
    $course = Course::factory()->create(['name' => 'EDDK_APP']);

    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 5,
        'hours_updated' => now(),
    ]);

    EndorsementActivity::create([
        'endorsement_id' => 999,
        'vatsim_id' => $user->vatsim_id,
        'position' => 'EDDK_APP',
        'activity_minutes' => 200,
    ]);

    $sector = FamiliarisationSector::create(['name' => 'KOLN', 'fir' => 'EDGG']);
    Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $sector->id]);

    $response = $this->get(UserResource::getUrl('edit', ['record' => $user]));

    $response->assertSuccessful();
    $response->assertSee('EDDK_APP');
    $response->assertSee('KOLN');
    $response->assertSee('EDGG');
});
