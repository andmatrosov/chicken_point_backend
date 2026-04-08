<?php

use App\Http\Controllers\Api\CountryController;
use Illuminate\Support\Facades\Route;

Route::get('country', [CountryController::class, 'show'])
    ->middleware(['detect.country', 'throttle:api.country']);
