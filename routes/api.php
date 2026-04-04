<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PrizeController;
use App\Http\Controllers\Api\ShopController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)
    ->prefix('auth')
    ->group(function (): void {
        Route::post('register', 'register')->middleware('throttle:api.register');
        Route::post('login', 'login')->middleware('throttle:api.login');
    });

// IMPORTANT:
// This endpoint MUST remain publicly accessible (no auth:sanctum).
// It supports optional Sanctum authentication to enrich the response
// with current_user_rank and current_user_score.
// Do NOT move this route into an auth-protected group.
Route::prefix('game')->group(function (): void {
    Route::controller(LeaderboardController::class)->group(function (): void {
        Route::get('leaderboard', 'index');
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::controller(AuthController::class)
        ->prefix('auth')
        ->group(function (): void {
            Route::post('logout', 'logout');
        });

    Route::get('me', [AuthController::class, 'me']);

    Route::controller(ProfileController::class)
        ->prefix('profile')
        ->group(function (): void {
            Route::get('/', 'profile')->middleware('throttle:api.profile');
            Route::get('skins', 'skins')->middleware('throttle:api.profile');
            Route::post('active-skin', 'setActiveSkin')->middleware(['request.signature', 'throttle:api.active-skin']);
            Route::get('rank', 'rank')->middleware('throttle:api.profile');
        });

    Route::prefix('game')->group(function (): void {
        Route::controller(GameSessionController::class)
            ->prefix('session')
            ->group(function (): void {
                Route::post('start', 'start')->middleware(['request.signature', 'throttle:api.session-start']);
            });

        Route::controller(GameController::class)->group(function (): void {
            Route::post('submit-score', 'submitScore')->middleware(['request.signature', 'throttle:api.submit-score']);
        });

        Route::controller(ShopController::class)
            ->prefix('shop')
            ->group(function (): void {
                Route::get('/', 'index');
                Route::post('buy-skin', 'buy')->middleware(['request.signature', 'throttle:api.buy-skin']);
            });

    });

    Route::prefix('prizes')->group(function (): void {
        Route::controller(PrizeController::class)->group(function (): void {
            Route::get('my', 'myPrizes');
        });
    });
});
