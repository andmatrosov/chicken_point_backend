<?php

use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ShopController;
use Illuminate\Support\Facades\Route;

// IMPORTANT:
// This endpoint MUST remain publicly accessible (no auth:sanctum).
// It supports optional Sanctum authentication to enrich the response
// with current_user_rank and current_user_score.
// Do NOT move this route into an auth-protected group.
Route::prefix('game')->group(function (): void {
    Route::controller(LeaderboardController::class)->group(function (): void {
        Route::get('leaderboard', 'index')->middleware('throttle:api.leaderboard');
    });

    Route::controller(ShopController::class)
        ->prefix('shop')
        ->group(function (): void {
            Route::get('/', 'index')->middleware('throttle:api.authenticated-read');
        });
});

Route::middleware('auth:sanctum')
    ->prefix('game')
    ->group(function (): void {
        Route::controller(GameSessionController::class)
            ->prefix('session')
            ->group(function (): void {
                Route::post('start', 'start')->middleware('throttle:api.session-start');
                Route::post('close', 'close')->middleware('throttle:api.session-close');
            });

        Route::controller(GameController::class)->group(function (): void {
            Route::post('submit-score', 'submitScore')->middleware('throttle:api.submit-score');
        });
        Route::controller(ShopController::class)
            ->prefix('shop')
            ->group(function (): void {
                Route::post('buy-skin', 'buy')->middleware('throttle:api.buy-skin');
            });
    });
