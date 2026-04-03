<?php

namespace Tests\Feature\Security;

use App\Models\Skin;
use App\Models\User;
use App\Services\RequestSignatureService;
use App\Services\SecurityEventLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class RequestSignatureMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('game.signature.enabled', true);
        config()->set('game.signature.secret', 'test-signature-secret');
        config()->set('game.signature.nonce_store', 'array');
    }

    public function test_valid_signature_is_accepted(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-client')->plainTextToken;

        $response = $this->signedJson(
            'POST',
            '/api/game/session/start',
            ['metadata' => ['device_id' => 'ios-device-1']],
            $token,
            'valid-signature-nonce',
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['session_token', 'expires_at'],
                'meta',
            ]);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $user = User::factory()->create([
            'coins' => 0,
        ]);

        $skin = Skin::query()->create([
            'title' => 'Signature Skin',
            'code' => 'signature-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $token = $user->createToken('mobile-client')->plainTextToken;

        $this->mock(SecurityEventLogger::class, function (MockInterface $mock): void {
            $mock->shouldReceive('logSignatureFailure')
                ->once()
                ->withArgs(fn ($request, string $reason): bool => $reason === 'invalid_signature');
        });

        $this->signedJson(
            'POST',
            '/api/game/shop/buy-skin',
            ['skin_id' => $skin->id],
            $token,
            'invalid-signature-nonce',
            signature: 'invalid-signature',
        )
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid request signature.');
    }

    public function test_reused_nonce_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-client')->plainTextToken;
        $payload = ['metadata' => ['device_id' => 'android-device-1']];
        $timestamp = now()->timestamp;
        $nonce = 'reused-nonce';

        $this->signedJson(
            'POST',
            '/api/game/session/start',
            $payload,
            $token,
            $nonce,
            $timestamp,
        )->assertOk();

        $this->mock(SecurityEventLogger::class, function (MockInterface $mock) use ($nonce): void {
            $mock->shouldReceive('logNonceReplay')
                ->once()
                ->withArgs(fn ($request, string $loggedNonce): bool => $loggedNonce === $nonce);
        });

        $this->signedJson(
            'POST',
            '/api/game/session/start',
            $payload,
            $token,
            $nonce,
            $timestamp,
        )
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Request nonce has already been used.');
    }

    public function test_expired_timestamp_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-client')->plainTextToken;

        $this->signedJson(
            'POST',
            '/api/game/session/start',
            ['metadata' => ['device_id' => 'ios-device-2']],
            $token,
            'expired-timestamp-nonce',
            now()->subMinutes(5)->timestamp,
        )
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Signature timestamp has expired.');
    }

    public function test_missing_signature_headers_are_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-client')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/game/session/start', ['metadata' => ['device_id' => 'ios-device-3']])
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Missing required signature headers.');
    }

    public function test_missing_signature_secret_returns_safe_error_message(): void
    {
        config()->set('game.signature.secret', null);

        $user = User::factory()->create();
        $token = $user->createToken('mobile-client')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/game/session/start', ['metadata' => ['device_id' => 'ios-device-4']])
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Request verification is unavailable.');
    }

    public function test_signature_canonicalizes_path_without_query_string(): void
    {
        $service = app(RequestSignatureService::class);

        $this->assertSame(
            $service->sign('POST', '/api/game/session/start?foo=bar', '{}', '123', 'nonce-1'),
            $service->sign('POST', '/api/game/session/start', '{}', '123', 'nonce-1'),
        );
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
