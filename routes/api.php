<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GdprController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FamiliarisationController;
use App\Http\Controllers\Api\Tier1Controller;
use App\Http\Controllers\Api\SoloController;
use App\Http\Controllers\Api\CptController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::middleware(['api.auth:gdpr.delete'])->group(function () {
    Route::delete('gdpr-removal/{vatsimId}', [GdprController::class, 'delete']);
});

Route::middleware(['api.auth:users.read'])->group(function () {
    Route::get('user-data/{vatsimId}', [UserController::class, 'show']);
});

Route::middleware(['api.auth:familiarisations.read'])->group(function () {
    Route::get('familiarisations', [FamiliarisationController::class, 'index']);
});

Route::middleware(['api.auth:tier1.read'])->group(function () {
    Route::get('tier1', [Tier1Controller::class, 'index']);
});

Route::middleware(['api.auth:solos.read'])->group(function () {
    Route::get('solos', [SoloController::class, 'index']);
});

Route::middleware(['api.auth:cpts.read'])->group(function () {
    Route::get('cpts', [CptController::class, 'index']);
});