<?php

use App\Models\User;
use Illuminate\Auth\Recaller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('remember-me cookie can be revived for a user with no local password', function () {
    $user = User::factory()->create(['password' => null]);
    $user->setRememberToken('a-valid-remember-token');
    $user->save();

    $recaller = new Recaller("{$user->id}|{$user->getRememberToken()}|{$user->getAuthPassword()}");

    $guard = Auth::guard('web');
    $revived = (fn () => $this->userFromRecaller($recaller))->call($guard);

    expect($revived)->not->toBeNull();
    expect($revived->id)->toBe($user->id);
});
