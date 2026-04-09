<?php

namespace Tests\Feature\GeoIp;

use App\Services\GeoIpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DetectCountryByIpMiddlewareTest extends TestCase
{
    public function test_forwarded_headers_are_ignored_when_no_trusted_proxy_is_configured(): void
    {
        Route::get('/test/client-ip-untrusted', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => [
                    'client_ip' => $request->ip(),
                ],
                'meta' => [],
            ]);
        });

        $this->withServerVariables([
            'REMOTE_ADDR' => '192.0.2.10',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.5',
        ])->getJson('/test/client-ip-untrusted')
            ->assertOk()
            ->assertJsonPath('data.client_ip', '192.0.2.10');
    }

    public function test_forwarded_headers_are_used_only_for_trusted_proxies(): void
    {
        config()->set('trustedproxy.proxies', 'REMOTE_ADDR');

        Route::get('/test/client-ip-trusted', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => [
                    'client_ip' => $request->ip(),
                ],
                'meta' => [],
            ]);
        });

        $this->withServerVariables([
            'REMOTE_ADDR' => '192.0.2.10',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.5',
        ])->getJson('/test/client-ip-trusted')
            ->assertOk()
            ->assertJsonPath('data.client_ip', '203.0.113.5');
    }

    public function test_middleware_adds_country_attributes_without_breaking_the_request(): void
    {
        app()->instance(GeoIpService::class, new class extends GeoIpService
        {
            public function detectFromRequest(Request $request): ?array
            {
                return ['code' => 'GE', 'name' => 'Georgia'];
            }
        });

        Route::middleware('detect.country')->get('/test/geoip-middleware', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => [
                    'geo_country_code' => $request->attributes->get('geo_country_code'),
                    'geo_country_name' => $request->attributes->get('geo_country_name'),
                ],
                'meta' => [],
            ]);
        });

        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->getJson('/test/geoip-middleware')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.geo_country_code', 'GE')
            ->assertJsonPath('data.geo_country_name', 'Georgia');
    }

    public function test_middleware_continues_when_detection_returns_null(): void
    {
        app()->instance(GeoIpService::class, new class extends GeoIpService
        {
            public function detectFromRequest(Request $request): ?array
            {
                return null;
            }
        });

        Route::middleware('detect.country')->get('/test/geoip-middleware-null', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => [
                    'geo_country_code' => $request->attributes->get('geo_country_code'),
                    'geo_country_name' => $request->attributes->get('geo_country_name'),
                ],
                'meta' => [],
            ]);
        });

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->getJson('/test/geoip-middleware-null')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.geo_country_code', null)
            ->assertJsonPath('data.geo_country_name', null);
    }
}
