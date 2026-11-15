<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;

// The admin panel runs in strict authorization mode: Filament throws instead of allowing an action
// whose policy (or policy method) is missing. Every resource model has to cover all the abilities
// its pages, table actions and bulk actions check.
test('the admin panel enforces strict authorization', function () {
    expect(Filament::getPanel('admin')->isAuthorizationStrict())->toBeTrue();
});

test('every admin panel resource model has a policy covering the abilities its actions check', function () {
    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        $model = $resource::getModel();
        $policy = Gate::getPolicyFor($model);

        expect($policy)->not->toBeNull("{$model} has no policy");

        foreach (['viewAny', 'view', 'create', 'update', 'delete', 'deleteAny'] as $ability) {
            expect(method_exists($policy, $ability))->toBeTrue(get_debug_type($policy)." is missing {$ability}()");
        }
    }
});
