<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\VatsimOAuthController;
use App\Http\Controllers\Auth\AdminAuthController;
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

    // Admin login routes (separate from regular user flow)
    Route::get('admin/login', [AdminAuthController::class, 'create'])
        ->name('admin.login');

    Route::post('admin/login', [AdminAuthController::class, 'store'])
        ->name('admin.login.store');
});

Route::middleware('auth')->group(function () {
    // Logout route (works for both VATSIM and admin users)
    Route::post('logout', [AdminAuthController::class, 'destroy'])
        ->name('logout');
});