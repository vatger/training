<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\VatsimOAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Regular user login (VATSIM OAuth only)
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // VATSIM OAuth routes
    Route::get('auth/vatsim', [VatsimOAuthController::class, 'redirect'])
        ->name('auth.vatsim');

    Route::get('auth/vatsim/callback', [VatsimOAuthController::class, 'callback'])
        ->name('auth.vatsim.callback');

    // VATSIM Connect sandbox login — dev-only, replaces the old admin backdoor.
    // Double-gated by the `sandbox.auth` middleware (see App\Support\SandboxAuth):
    // never reachable in production, even if APP_ENV is misconfigured.
    Route::middleware('sandbox.auth')->group(function () {
        Route::get('auth/vatsim/sandbox', [VatsimOAuthController::class, 'sandboxRedirect'])
            ->name('auth.vatsim.sandbox');

        Route::get('auth/vatsim/sandbox/callback', [VatsimOAuthController::class, 'sandboxCallback'])
            ->name('auth.vatsim.sandbox.callback');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
