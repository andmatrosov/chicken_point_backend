<?php

namespace Tests\Unit;

use App\Services\GeoIpService;
use Illuminate\Http\Request;
use Tests\TestCase;

class GeoIpServiceTest extends TestCase
{
    public function test_it_returns_null_for_empty_invalid_and_private_ips(): void
    {
        $service = new class extends GeoIpService
        {
            protected function databaseExists(): bool
            {
                return true;
            }

            protected function lookupCountry(string $ip): ?array
            {
                return ['code' => 'DE', 'name' => 'Germany'];
            }
        };

        $this->assertNull($service->detectCountry(null));
        $this->assertNull($service->detectCountryCode(null));
        $this->assertNull($service->detectCountry(''));
        $this->assertNull($service->detectCountry('localhost'));
        $this->assertNull($service->detectCountry('invalid-ip'));
        $this->assertNull($service->detectCountry('127.0.0.1'));
        $this->assertNull($service->detectCountry('10.0.0.5'));
        $this->assertNull($service->detectCountry('192.168.1.20'));
    }

    public function test_it_returns_country_payload_and_code_for_public_ip(): void
    {
        $service = new class extends GeoIpService
        {
            protected function databaseExists(): bool
            {
                return true;
            }

            protected function lookupCountry(string $ip): ?array
            {
                return ['code' => 'US', 'name' => 'United States'];
            }
        };

        $this->assertSame([
            'code' => 'US',
            'name' => 'United States',
        ], $service->detectCountry('8.8.8.8'));
        $this->assertSame('US', $service->detectCountryCode('8.8.8.8'));
    }

    public function test_it_detects_country_from_request(): void
    {
        $service = new class extends GeoIpService
        {
            protected function databaseExists(): bool
            {
                return true;
            }

            protected function lookupCountry(string $ip): ?array
            {
                return ['code' => 'FR', 'name' => 'France'];
            }
        };

        $request = Request::create('/geoip-demo', 'GET', server: [
            'REMOTE_ADDR' => '8.8.4.4',
        ]);

        $this->assertSame([
            'code' => 'FR',
            'name' => 'France',
        ], $service->detectFromRequest($request));
    }

    public function test_it_fails_closed_when_lookup_throws(): void
    {
        $service = new class extends GeoIpService
        {
            protected function databaseExists(): bool
            {
                return true;
            }

            protected function lookupCountry(string $ip): ?array
            {
                throw new \RuntimeException('lookup failed');
            }
        };

        $this->assertNull($service->detectCountry('1.1.1.1'));
        $this->assertNull($service->detectCountryCode('1.1.1.1'));
    }
}
