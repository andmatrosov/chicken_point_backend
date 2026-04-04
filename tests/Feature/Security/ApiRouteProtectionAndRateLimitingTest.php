<?php

namespace Tests\Feature\Security;

use App\Models\Skin;
use App\Models\User;
use App\Services\RequestSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRouteProtectionAndRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('game.signature.enabled', true);
        config()->set('game.signature.secret', 'test-signature-secret');
        config()->set('game.signature.nonce_store', 'array');
    }

    public function test_sensitive_api_routes_require_authentication(): void
    {
        $requests = [
            ['method' => 'getJson', 'uri' => '/api/me', 'payload' => []],
            ['method' => 'getJson', 'uri' => '/api/profile', 'payload' => []],
            ['method' => 'getJson', 'uri' => '/api/profile/skins', 'payload' => []],
            ['method' => 'getJson', 'uri' => '/api/profile/rank', 'payload' => []],
            ['method' => 'getJson', 'uri' => '/api/game/shop', 'payload' => []],
            ['method' => 'postJson', 'uri' => '/api/auth/logout', 'payload' => []],
            ['method' => 'postJson', 'uri' => '/api/profile/active-skin', 'payload' => ['skin_id' => 1]],
            ['method' => 'postJson', 'uri' => '/api/game/shop/buy-skin', 'payload' => ['skin_id' => 1]],
            ['method' => 'postJson', 'uri' => '/api/game/session/start', 'payload' => []],
            ['method' => 'postJson', 'uri' => '/api/game/submit-score', 'payload' => ['session_token' => 'missing', 'score' => 100]],
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
                ])
                ->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.1'])
            ->postJson('/api/auth/login', [
                'email' => $email,
                'password' => 'wrong-password',
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

    public function test_submit_score_is_rate_limited_per_authenticated_user(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('mobile-client')->plainTextToken;

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $response = $this->signedJson(
                'POST',
                '/api/game/submit-score',
                [
                    'session_token' => 'missing-session-token',
                    'score' => 100,
                ],
                $plainTextToken,
                'submit-score-'.$attempt,
            );

            $response
                ->assertStatus(422);
        }

        $this->signedJson(
            'POST',
            '/api/game/submit-score',
            [
                'session_token' => 'missing-session-token',
                'score' => 100,
            ],
            $plainTextToken,
            'submit-score-rate-limited',
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

        $plainTextToken = $user->createToken('mobile-client')->plainTextToken;

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $response = $this->signedJson(
                'POST',
                '/api/game/shop/buy-skin',
                [
                    'skin_id' => $skin->id,
                ],
                $plainTextToken,
                'buy-skin-'.$attempt,
            );

            $response
                ->assertStatus(422);
        }

        $this->signedJson(
            'POST',
            '/api/game/shop/buy-skin',
            [
                'skin_id' => $skin->id,
            ],
            $plainTextToken,
            'buy-skin-rate-limited',
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

        $plainTextToken = $user->createToken('mobile-client')->plainTextToken;

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $targetSkinId = $attempt % 2 === 0 ? $firstSkin->id : $secondSkin->id;

            $this->signedJson(
                'POST',
                '/api/profile/active-skin',
                [
                    'skin_id' => $targetSkinId,
                ],
                $plainTextToken,
                'active-skin-'.$attempt,
            )
                ->assertOk();
        }

        $this->signedJson(
            'POST',
            '/api/profile/active-skin',
            [
                'skin_id' => $firstSkin->id,
            ],
            $plainTextToken,
            'active-skin-rate-limited',
        )
            ->assertTooManyRequests();
    }

    protected function signedJson(
        string $method,
        string $uri,
        array $payload,
        string $plainTextToken,
        string $nonce,
        ?int $timestamp = null,
        ?string $signature = null,
    ) {
        $timestamp ??= now()->timestamp;
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature ??= app(RequestSignatureService::class)->sign(
            $method,
            $uri,
            $body,
            (string) $timestamp,
            $nonce,
        );

        return $this
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-Timestamp', (string) $timestamp)
            ->withHeader('X-Nonce', $nonce)
            ->withHeader('X-Signature', $signature)
            ->json($method, $uri, $payload);
    }
}
