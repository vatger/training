<?php

use App\Http\Controllers\S1\S1AdminController;
use App\Http\Controllers\S1\S1AttendanceController;
use App\Http\Controllers\S1\S1SessionController;
use App\Http\Controllers\S1\S1WaitingListController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('s1')->group(function () {
    
    Route::prefix('waiting-lists')->group(function () {
        Route::get('/', [S1WaitingListController::class, 'index']);
        Route::post('/modules/{module}/join', [S1WaitingListController::class, 'join']);
        Route::delete('/modules/{module}/leave', [S1WaitingListController::class, 'leave']);
        Route::post('/{waitingList}/confirm', [S1WaitingListController::class, 'confirm']);
        Route::get('/modules/{module}/position', [S1WaitingListController::class, 'position']);
    });

    Route::prefix('sessions')->group(function () {
        Route::get('/', [S1SessionController::class, 'index']);
        Route::post('/', [S1SessionController::class, 'store']);
        Route::get('/{session}', [S1SessionController::class, 'show']);
        Route::put('/{session}', [S1SessionController::class, 'update']);
        Route::delete('/{session}', [S1SessionController::class, 'destroy']);
        
        Route::post('/{session}/signup', [S1SessionController::class, 'signup']);
        Route::delete('/{session}/signup', [S1SessionController::class, 'cancelSignup']);
        Route::post('/{session}/lock-signups', [S1SessionController::class, 'lockSignups']);
        Route::post('/{session}/select-participants', [S1SessionController::class, 'selectParticipants']);
    });

    Route::prefix('sessions/{session}/attendance')->group(function () {
        Route::get('/', [S1AttendanceController::class, 'index']);
        Route::post('/users/{user}', [S1AttendanceController::class, 'mark']);
        Route::post('/bulk', [S1AttendanceController::class, 'markBulk']);
        Route::post('/spontaneous', [S1AttendanceController::class, 'addSpontaneous']);
    });

    Route::get('/attendance/history', [S1AttendanceController::class, 'userHistory']);

    Route::prefix('admin')->group(function () {
        Route::post('/users/{user}/ban', [S1AdminController::class, 'banUser']);
        Route::delete('/users/{user}/unban', [S1AdminController::class, 'unbanUser']);
        Route::get('/bans', [S1AdminController::class, 'listBans']);
        
        Route::post('/users/{user}/reset-progress', [S1AdminController::class, 'resetProgress']);
        Route::get('/users/{user}/reset-history', [S1AdminController::class, 'resetHistory']);
        
        Route::post('/users/{user}/comments', [S1AdminController::class, 'addComment']);
        Route::get('/users/{user}/comments', [S1AdminController::class, 'getComments']);
        
        Route::get('/mentor-stats', [S1AdminController::class, 'mentorStats']);
        Route::get('/mentor-stats/{mentor}', [S1AdminController::class, 'mentorStats']);
        
        Route::get('/users/{user}/progress', [S1AdminController::class, 'userProgress']);
    });
});