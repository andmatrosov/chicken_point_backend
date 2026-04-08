<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->successResponse([
            'country_code' => $request->attributes->get('geo_country_code'),
            'country_name' => $request->attributes->get('geo_country_name'),
        ]);
    }
}
