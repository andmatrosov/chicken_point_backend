<?php

use App\Http\Controllers\Api\PrizeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('prizes')
    ->group(function (): void {
        Route::controller(PrizeController::class)->group(function (): void {
            Route::get('my', 'myPrizes');
        });
    });
