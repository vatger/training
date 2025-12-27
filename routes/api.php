<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GdprController;
use App\Http\Controllers\Api\UserController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::middleware(['api.auth:gdpr.delete'])->group(function () {
    Route::delete('gdpr-removal/{vatsimId}', [GdprController::class, 'delete']);
});

Route::middleware(['api.auth:users.read'])->group(function () {
    Route::get('user-data/{vatsimId}', [UserController::class, 'show']);
});