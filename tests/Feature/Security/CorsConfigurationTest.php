<?php

namespace Tests\Feature\Security;

use App\Services\GeoIpService;
use Tests\TestCase;

class CorsConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        $this->configureAllowedOrigins(['https://app.example.com']);
    }

    public function test_get_request_from_allowed_origin_receives_cors_headers(): void
    {
        $this->withHeaders([
            'Origin' => 'https://app.example.com',
            'Accept' => 'application/json',
        ])->getJson('/api/country')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com')
            ->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    public function test_post_request_from_allowed_origin_receives_cors_headers(): void
    {
        $this->withHeaders([
            'Origin' => 'https://app.example.com',
            'Accept' => 'application/json',
        ])->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com');
    }

    public function test_preflight_request_from_allowed_origin_is_handled(): void
    {
        $this->call('OPTIONS', '/api/auth/login', [], [], [], [
            'HTTP_ORIGIN' => 'https://app.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization, Content-Type',
        ])->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com')
            ->assertHeader('Access-Control-Allow-Methods');
    }

    public function test_disallowed_origin_receives_a_forbidden_json_response(): void
    {
        $this->withHeaders([
            'Origin' => 'https://evil-example.com',
            'Accept' => 'application/json',
        ])->getJson('/api/country')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Origin is not allowed.',
            ])
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_request_without_origin_header_is_not_blocked(): void
    {
        $this->getJson('/api/country')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    /**
     * @param  array<int, string>  $origins
     */
    private function configureAllowedOrigins(array $origins): void
    {
        config()->set('cors.allowed_origins', $origins);
        config()->set('cors.allowed_origins_patterns', \App\Support\FrontendOrigins::patterns($origins));
    }
}
