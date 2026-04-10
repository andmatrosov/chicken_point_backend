<?php

namespace Tests\Feature\Security;

use App\Models\Skin;
use App\Models\User;
use App\Services\GeoIpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRouteProtectionAndRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_api_routes_require_authentication(): void
    {
        $requests = [
            ['method' => 'getJson', 'uri' => '/api/me', 'payload' => []],
            ['method' => 'getJson', 'uri' => '/api/profile', 'payload' => []],
            ['method' => 'getJson', 'uri' => '/api/profile/skins', 'payload' => []],
            ['method' => 'getJson', 'uri' => '/api/profile/rank', 'payload' => []],
            ['method' => 'postJson', 'uri' => '/api/auth/logout', 'payload' => []],
            ['method' => 'postJson', 'uri' => '/api/auth/logout-all-devices', 'payload' => []],
            ['method' => 'postJson', 'uri' => '/api/profile/active-skin', 'payload' => ['skin_id' => 1]],
            ['method' => 'postJson', 'uri' => '/api/game/shop/buy-skin', 'payload' => ['skin_id' => 1]],
            ['method' => 'postJson', 'uri' => '/api/game/session/start', 'payload' => []],
            ['method' => 'postJson', 'uri' => '/api/game/submit-score', 'payload' => ['session_token' => 'missing', 'score' => 100, 'coins_collected' => 0]],
            ['method' => 'getJson', 'uri' => '/api/prizes/my', 'payload' => []],
        ];

        foreach ($requests as $request) {
            $response = $this->{$request['method']}($request['uri'], $request['payload']);

            $response->assertUnauthorized();
        }
    }

    public function test_login_is_rate_limited_by_ip_and_email(): void
    {
        $email = 'player@example.com';

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.1'])
                ->postJson('/api/auth/login', [
                    'email' => $email,
                    'password' => 'wrong-password',
                    'device_id' => 'android-device-login-rate-limit',
                    'platform' => 'android',
                    'app_version' => '1.0.0',
                ])
                ->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.1'])
            ->postJson('/api/auth/login', [
                'email' => $email,
                'password' => 'wrong-password',
                'device_id' => 'android-device-login-rate-limit',
                'platform' => 'android',
                'app_version' => '1.0.0',
            ])
            ->assertTooManyRequests();
    }

    public function test_register_is_rate_limited_by_ip(): void
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.2'])
                ->postJson('/api/auth/register', [])
                ->assertUnprocessable();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.2'])
            ->postJson('/api/auth/register', [])
            ->assertTooManyRequests();
    }

    public function test_me_is_rate_limited_per_authenticated_user(): void
    {
        config()->set('game.rate_limits.authenticated_read_per_minute', 2);

        $user = User::factory()->create();
        $plainTextToken = $user->createToken('mobile-client')->plainTextToken;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
                ->getJson('/api/me')
                ->assertOk();
        }

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/me')
            ->assertTooManyRequests();
    }

    public function test_public_shop_is_rate_limited_by_ip_without_requiring_authentication(): void
    {
        config()->set('game.rate_limits.authenticated_read_per_minute', 2);

        Skin::query()->create([
            'title' => 'Public Shop Skin',
            'code' => 'public-shop-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.34'])
                ->getJson('/api/game/shop')
                ->assertOk()
                ->assertJsonPath('success', true);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.34'])
            ->getJson('/api/game/shop')
            ->assertTooManyRequests();
    }

    public function test_my_prizes_is_rate_limited_per_authenticated_user(): void
    {
        config()->set('game.rate_limits.authenticated_read_per_minute', 2);

        $user = User::factory()->create();
        $plainTextToken = $user->createToken('mobile-client')->plainTextToken;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
                ->getJson('/api/prizes/my')
                ->assertOk();
        }

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/prizes/my')
            ->assertTooManyRequests();
    }

    public function test_logout_is_rate_limited_per_authenticated_user(): void
    {
        config()->set('game.rate_limits.auth_token_management_per_minute', 1);

        $user = User::factory()->create();
        $firstToken = $user->createToken('mobile-client')->plainTextToken;
        $secondToken = $user->createToken('mobile-client:backup')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$firstToken)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->postJson('/api/auth/logout')
            ->assertTooManyRequests();
    }

    public function test_logout_all_devices_is_rate_limited_per_authenticated_user(): void
    {
        config()->set('game.rate_limits.auth_token_management_per_minute', 1);

        $user = User::factory()->create();
        $firstToken = $user->createToken('mobile-client')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$firstToken)
            ->postJson('/api/auth/logout-all-devices')
            ->assertOk();

        $secondToken = $user->createToken('mobile-client:new')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->postJson('/api/auth/logout-all-devices')
            ->assertTooManyRequests();
    }

    public function test_submit_score_is_rate_limited_per_authenticated_user(): void
    {
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $response = $this->bearerJsonAsUser(
                $user,
                'POST',
                '/api/game/submit-score',
                [
                    'session_token' => 'missing-session-token',
                    'score' => 100,
                    'coins_collected' => 0,
                ],
            );

            $response
                ->assertStatus(422);
        }

        $this->bearerJsonAsUser(
            $user,
            'POST',
            '/api/game/submit-score',
            [
                'session_token' => 'missing-session-token',
                'score' => 100,
                'coins_collected' => 0,
            ],
        )
            ->assertTooManyRequests();
    }

    public function test_buy_skin_is_rate_limited_per_authenticated_user(): void
    {
        $user = User::factory()->create([
            'coins' => 0,
        ]);

        $skin = Skin::query()->create([
            'title' => 'Rate Limited Skin',
            'code' => 'rate-limited-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $response = $this->bearerJsonAsUser(
                $user,
                'POST',
                '/api/game/shop/buy-skin',
                [
                    'skin_id' => $skin->id,
                ],
            );

            $response
                ->assertStatus(422);
        }

        $this->bearerJsonAsUser(
            $user,
            'POST',
            '/api/game/shop/buy-skin',
            [
                'skin_id' => $skin->id,
            ],
        )
            ->assertTooManyRequests();
    }

    public function test_active_skin_is_rate_limited_per_authenticated_user(): void
    {
        $user = User::factory()->create();

        $firstSkin = Skin::query()->create([
            'title' => 'First Active Skin',
            'code' => 'first-active-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $secondSkin = Skin::query()->create([
            'title' => 'Second Active Skin',
            'code' => 'second-active-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $user->skins()->attach($firstSkin->id, ['purchased_at' => now()->subMinute()]);
        $user->skins()->attach($secondSkin->id, ['purchased_at' => now()]);

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $targetSkinId = $attempt % 2 === 0 ? $firstSkin->id : $secondSkin->id;

            $this->bearerJsonAsUser(
                $user,
                'POST',
                '/api/profile/active-skin',
                [
                    'skin_id' => $targetSkinId,
                ],
            )
                ->assertOk();
        }

        $this->bearerJsonAsUser(
            $user,
            'POST',
            '/api/profile/active-skin',
            [
                'skin_id' => $firstSkin->id,
            ],
        )
            ->assertTooManyRequests();
    }

    public function test_country_endpoint_is_public_and_rate_limited_by_ip(): void
    {
        config()->set('game.rate_limits.country_check_per_minute', 2);

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

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.30'])
                ->getJson('/api/country')
                ->assertOk()
                ->assertJsonPath('success', true);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.30'])
            ->getJson('/api/country')
            ->assertTooManyRequests();
    }

    public function test_public_mvp_settings_endpoints_are_rate_limited_by_ip(): void
    {
        config()->set('game.rate_limits.mvp_settings_per_minute', 2);

        $requests = [
            ['/api/mvp-settings/main', '10.10.0.31', 'main'],
            ['/api/mvp-settings/brazil', '10.10.0.32', 'brazil'],
        ];

        foreach ($requests as [$uri, $ipAddress, $version]) {
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
                    ->getJson($uri)
                    ->assertOk()
                    ->assertJsonPath('success', true)
                    ->assertJsonPath('data.version', $version);
            }

            $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
                ->getJson($uri)
                ->assertTooManyRequests();
        }
    }

    public function test_public_leaderboard_is_rate_limited_by_ip_without_requiring_authentication(): void
    {
        config()->set('game.rate_limits.leaderboard_per_minute', 2);

        User::factory()->count(3)->sequence(
            ['email' => 'first@example.com', 'best_score' => 900],
            ['email' => 'second@example.com', 'best_score' => 800],
            ['email' => 'third@example.com', 'best_score' => 700],
        )->create();

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.33'])
                ->getJson('/api/game/leaderboard')
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonMissingPath('data.current_user_rank')
                ->assertJsonMissingPath('data.current_user_score');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.33'])
            ->getJson('/api/game/leaderboard')
            ->assertTooManyRequests();
    }
}
