<?php

use App\Domain\Gdpr\UserDataMap;
use App\Filament\Resources\GdprRemovalRequests\GdprRemovalRequestResource;
use App\Filament\Resources\GdprRemovalRequests\Pages\ListGdprRemovalRequests;
use App\Filament\Resources\GdprRemovalRequests\Pages\ViewGdprRemovalRequest;
use App\Filament\Resources\GdprRemovalRequests\RelationManagers\ReviewItemsRelationManager;
use App\Filament\Resources\Users\UserResource;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\GdprRemovalRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatEudClientInterface::class, FakeVatEudClient::class);
    Http::fake(['*' => Http::response([], 200)]);
});

function leitungUser(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::firstOrCreate(['name' => 'VATGER Leitung']));

    return $user;
}

function pendingGdprRequest(array $userAttributes = []): GdprRemovalRequest
{
    $user = User::factory()->create($userAttributes + ['vatsim_id' => 8000001]);

    return GdprRemovalRequest::create([
        'user_id' => $user->id,
        'vatsim_id' => $user->vatsim_id,
        'user_name' => $user->name,
        'search_terms' => UserDataMap::searchTerms($user),
    ]);
}

test('VATGER Leitung can open the admin panel and the GDPR requests', function () {
    $this->actingAs(leitungUser());

    expect(GdprRemovalRequestResource::canViewAny())->toBeTrue();
    $this->get(GdprRemovalRequestResource::getUrl('index'))->assertSuccessful();
});

test('admins can review GDPR requests too', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    expect(GdprRemovalRequestResource::canViewAny())->toBeTrue();
});

test('superusers can review GDPR requests too', function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));

    expect(GdprRemovalRequestResource::canViewAny())->toBeTrue();
});

test('mentors and leading mentors cannot see GDPR requests', function (array $attributes, ?string $role) {
    $user = User::factory()->create($attributes);
    if ($role) {
        $user->roles()->attach(Role::firstOrCreate(['name' => $role]));
    }
    $this->actingAs($user);

    expect(GdprRemovalRequestResource::canViewAny())->toBeFalse();
})->with([
    'ATD Leitung' => [[], 'ATD Leitung'],
    'mentor' => [[], 'EDGG Mentor'],
]);

test('a user with the Leitung role is let into the panel', function () {
    expect(leitungUser()->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeTrue();
});

test('the list shows the requests', function () {
    $this->actingAs(leitungUser());
    $request = pendingGdprRequest();

    Livewire::test(ListGdprRemovalRequests::class)
        ->assertCanSeeTableRecords([$request]);
});

test('the pending request shows all stored data and highlights the identifiers', function () {
    $this->actingAs(leitungUser());
    $request = pendingGdprRequest(['first_name' => 'Erika', 'last_name' => 'Musterfrau']);

    Livewire::test(ViewGdprRemovalRequest::class, ['record' => $request->getKey()])
        ->assertSuccessful()
        ->assertSee('Stored data of this user')
        ->assertSee('Erika')
        ->assertSee('8000001')
        ->assertActionVisible('approve');
});

test('approving deletes the user and moves the request to review', function () {
    $this->actingAs(leitungUser());
    $request = pendingGdprRequest();
    $userId = $request->user_id;
    createTrainingLog(['trainee_id' => User::factory()->create()->id, 'mentor_id' => $userId]);

    Livewire::test(ViewGdprRemovalRequest::class, ['record' => $request->getKey()])
        ->callAction('approve')
        ->assertNotified();

    expect(User::find($userId))->toBeNull()
        ->and($request->fresh()->isApproved())->toBeTrue()
        ->and($request->fresh()->reviewItems)->toHaveCount(1);
});

test('approving without any records to review closes the request', function () {
    $this->actingAs(leitungUser());
    $request = pendingGdprRequest();

    Livewire::test(ViewGdprRemovalRequest::class, ['record' => $request->getKey()])->callAction('approve');

    expect($request->fresh()->isCompleted())->toBeTrue();
});

test('the approve action is gone once the request is approved', function () {
    $this->actingAs(leitungUser());
    $request = pendingGdprRequest();
    $request->update(['status' => GdprRemovalRequest::STATUS_COMPLETED]);

    Livewire::test(ViewGdprRemovalRequest::class, ['record' => $request->getKey()])
        ->assertActionHidden('approve');
});

test('leitung can edit a retained record and mark it as reviewed, which closes the request', function () {
    $this->actingAs(leitungUser());
    $request = pendingGdprRequest(['first_name' => 'Erika', 'last_name' => 'Musterfrau']);
    $log = createTrainingLog([
        'trainee_id' => User::factory()->create()->id,
        'mentor_id' => $request->user_id,
        'final_comment' => 'Erika Musterfrau war dabei',
    ]);

    Livewire::test(ViewGdprRemovalRequest::class, ['record' => $request->getKey()])->callAction('approve');
    $item = $request->fresh()->reviewItems->sole();

    Livewire::test(ReviewItemsRelationManager::class, ['ownerRecord' => $request->fresh(), 'pageClass' => ViewGdprRemovalRequest::class])
        ->assertCanSeeTableRecords([$item])
        ->mountTableAction('review', $item)
        ->assertTableActionDataSet(['final_comment' => 'Erika Musterfrau war dabei'])
        ->setTableActionData(['final_comment' => 'Jemand war dabei'])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    expect($log->fresh()->final_comment)->toBe('Jemand war dabei')
        ->and($item->fresh()->isReviewed())->toBeTrue()
        ->and($request->fresh()->isCompleted())->toBeTrue();
});

test('the removed user is kept out of user lists', function () {
    $this->actingAs(leitungUser());
    $request = pendingGdprRequest();
    $other = User::factory()->create();
    createTrainingLog(['trainee_id' => $other->id, 'mentor_id' => $request->user_id]);

    Livewire::test(ViewGdprRemovalRequest::class, ['record' => $request->getKey()])->callAction('approve');

    $placeholder = User::gdprPlaceholder();
    expect($placeholder->name)->toBe('GDPR Removed')
        ->and(UserResource::getEloquentQuery()->whereKey($placeholder->id)->exists())->toBeFalse();
});
