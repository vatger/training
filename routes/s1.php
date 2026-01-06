<?php

use App\Http\Controllers\S1\S1AdminController;
use App\Http\Controllers\S1\S1AttendanceController;
use App\Http\Controllers\S1\S1SessionController;
use App\Http\Controllers\S1\S1WaitingListController;
use App\Http\Controllers\S1\S1TrainingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::prefix('s1')->name('s1.')->group(function () {

        // Main training page - shows step-by-step journey
        Route::get('/training', [S1TrainingController::class, 'index'])->name('training');

        // Waiting list management
        Route::post('/waiting-list/{module}/join', [S1WaitingListController::class, 'join'])->name('waiting-list.join');
        Route::post('/waiting-list/{module}/leave', [S1WaitingListController::class, 'leave'])->name('waiting-list.leave');
        Route::post('/waiting-list/{waitingList}/confirm', [S1WaitingListController::class, 'confirm'])->name('waiting-list.confirm');
        // Changed from {module} to {waitingList} to match controller signature

        // Session signups
        Route::post('/session/{session}/signup', [S1SessionController::class, 'signup'])->name('session.signup');
        Route::post('/session/{session}/cancel', [S1SessionController::class, 'cancelSignup'])->name('session.cancel');
        // Note: Changed 'cancel' to 'cancelSignup' to match your controller method name

        // Rating upgrade request (to be implemented)
        // Route::post('/request-upgrade', [S1UpgradeController::class, 'request'])->name('request-upgrade');
    });
});