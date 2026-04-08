<?php

namespace Tests\Feature\GeoIp;

use App\Services\GeoIpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_endpoint_returns_detected_country_for_the_request_ip(): void
    {
        app()->instance(GeoIpService::class, new class extends GeoIpService
        {
            public function detectCountry(?string $ip): ?array
            {
                return [
                    'code' => 'GE',
                    'name' => 'Georgia',
                ];
            }
        });

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->getJson('/api/country')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.country_code', 'GE')
            ->assertJsonPath('data.country_name', 'Georgia');
    }

    public function test_country_endpoint_returns_null_fields_when_geoip_detection_fails(): void
    {
        app()->instance(GeoIpService::class, new class extends GeoIpService
        {
            public function detectCountry(?string $ip): ?array
            {
                return null;
            }
        });

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.15'])
            ->getJson('/api/country')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.country_code', null)
            ->assertJsonPath('data.country_name', null);
    }
}
