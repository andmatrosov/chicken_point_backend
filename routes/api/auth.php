<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)
    ->prefix('auth')
    ->group(function (): void {
        Route::post('register', 'register')->middleware('throttle:api.register');
        Route::post('login', 'login')->middleware('throttle:api.login');
    });

Route::middleware('auth:sanctum')
    ->group(function (): void {
        Route::controller(AuthController::class)
            ->prefix('auth')
            ->group(function (): void {
                Route::post('logout', 'logout');
                Route::post('logout-all-devices', 'logoutAllDevices');
            });

        Route::get('me', [AuthController::class, 'me']);
    });
