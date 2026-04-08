<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class CountryPaths
{
    #[OA\Get(
        path: '/api/country',
        operationId: 'detectCountryByRequestIp',
        tags: ['GeoIP'],
        summary: 'Detect the country for the current request IP',
        description: 'Public endpoint that resolves country_code and country_name from the current request IP using the local MaxMind GeoIP database. When the IP is private, invalid, or the database is unavailable, both fields return null.',
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/CountryResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function show(): void
    {
    }
}
