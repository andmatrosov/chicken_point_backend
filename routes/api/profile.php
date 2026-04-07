<?php

use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->controller(ProfileController::class)
    ->prefix('profile')
    ->group(function (): void {
        Route::get('/', 'profile')->middleware('throttle:api.profile');
        Route::get('skins', 'skins')->middleware('throttle:api.profile');
        Route::post('active-skin', 'setActiveSkin')->middleware('throttle:api.active-skin');
        Route::get('rank', 'rank')->middleware('throttle:api.profile');
    });
