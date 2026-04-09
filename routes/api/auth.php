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
                Route::post('logout', 'logout')->middleware('throttle:api.auth-token-management');
                Route::post('logout-all-devices', 'logoutAllDevices')->middleware('throttle:api.auth-token-management');
            });

        Route::get('me', [AuthController::class, 'me'])->middleware('throttle:api.authenticated-read');
    });
