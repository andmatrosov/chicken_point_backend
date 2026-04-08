<?php

use App\Http\Controllers\Api\MvpSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('mvp-settings')
    ->middleware('throttle:api.mvp-settings')
    ->group(function (): void {
        Route::controller(MvpSettingController::class)->group(function (): void {
            Route::get('main', 'main');
            Route::get('brazil', 'brazil');
        });
    });
