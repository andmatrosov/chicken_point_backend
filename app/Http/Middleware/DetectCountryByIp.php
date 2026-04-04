<?php

namespace App\Http\Middleware;

use App\Services\GeoIpService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectCountryByIp
{
    public function __construct(
        protected GeoIpService $geoIpService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $country = $this->geoIpService->detectFromRequest($request);

        $request->attributes->set('geo_country', $country);
        $request->attributes->set('geo_country_code', $country['code'] ?? null);
        $request->attributes->set('geo_country_name', $country['name'] ?? null);

        return $next($request);
    }
}
