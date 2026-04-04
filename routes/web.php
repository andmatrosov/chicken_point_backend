<?php

use App\Services\GeoIpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

if (app()->environment('local')) {
    Route::get('/geoip-demo', function (Request $request, GeoIpService $geoIpService) {
        return response()->json([
            'success' => true,
            'data' => [
                'request_ip' => $request->ip(),
                'geo_country_code' => $request->attributes->get('geo_country_code'),
                'geo_country_name' => $request->attributes->get('geo_country_name'),
                'direct_lookup' => $geoIpService->detectCountry($request->ip()),
            ],
            'meta' => [],
        ]);
    })->middleware('detect.country');
}
