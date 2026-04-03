<?php

namespace Tests\Feature\Game;

use App\Enums\GameSessionStatus;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSessionAndScoreApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('game.signature.enabled', true);
        config()->set('game.signature.secret', 'test-signature-secret');
        config()->set('game.signature.nonce_store', 'array');
    }

    public function test_start_session_returns_a_session_token_and_persists_an_active_session(): void
    {
        $user = User::factory()->create();

        $response = $this->signedJsonAsUser($user, 'POST', '/api/game/session/start', [
            'metadata' => [
                'device_id' => 'ios-device-1',
                'platform' => 'ios',
                'app_version' => '1.0.0',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['session_token', 'expires_at'],
                'meta',
            ]);

        $sessionToken = $response->json('data.session_token');

        $this->assertDatabaseHas('game_sessions', [
            'user_id' => $user->id,
            'token' => $sessionToken,
            'status' => GameSessionStatus::ACTIVE->value,
        ]);
    }

    public function test_submit_score_creates_score_marks_session_submitted_updates_best_score_and_awards_collected_coins(): void
    {
        $user = User::factory()->create([
            'best_score' => 150,
            'coins' => 10,
        ]);

        $session = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'submit-score-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(10),
            'metadata' => ['device_id' => 'ios-device-1'],
        ]);

        $this->assertTrue($session->id > 0);

        $this->signedJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'submit-score-session',
            'score' => 420,
            'metadata' => [
                'duration' => 120,
                'coins_collected' => 8,
                'device_id' => 'ios-device-1',
                'app_version' => '1.0.0',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.best_score', 420)
            ->assertJsonPath('data.coins', 18)
            ->assertJsonPath('data.current_rank', 1);

        $this->assertDatabaseHas('game_scores', [
            'user_id' => $user->id,
            'score' => 420,
            'session_token' => 'submit-score-session',
            'is_processed' => true,
        ]);
        $this->assertDatabaseHas('game_sessions', [
            'id' => $session->id,
            'status' => GameSessionStatus::SUBMITTED->value,
        ]);
        $this->assertSame(420, $user->fresh()->best_score);
        $this->assertSame(18, $user->fresh()->coins);
    }

    public function test_submit_score_rejects_duplicate_session_submissions(): void
    {
        $user = User::factory()->create([
            'best_score' => 100,
            'coins' => 5,
        ]);

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'duplicate-session-token',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->signedJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'duplicate-session-token',
            'score' => 200,
            'metadata' => [
                'duration' => 60,
                'coins_collected' => 7,
            ],
        ], nonce: 'submit-once')
            ->assertOk();

        $this->signedJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'duplicate-session-token',
            'score' => 210,
            'metadata' => ['duration' => 70],
        ], nonce: 'submit-twice')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This session has already been submitted.')
            ->assertJsonPath('errors.session_token.0', 'The provided session token has already been used.');

        $this->assertSame(12, $user->fresh()->coins);
    }

    public function test_submit_score_rejects_expired_sessions(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'expired-session-token',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinute(),
        ]);

        $this->signedJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'expired-session-token',
            'score' => 300,
            'metadata' => ['duration' => 100],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This session has expired.')
            ->assertJsonPath('errors.session_token.0', 'The provided session token has expired.');
    }

    public function test_submit_score_rejects_sessions_owned_by_another_user(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $owner->id,
            'token' => 'foreign-session-token',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->signedJsonAsUser($intruder, 'POST', '/api/game/submit-score', [
            'session_token' => 'foreign-session-token',
            'score' => 250,
            'metadata' => ['duration' => 80],
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This session does not belong to the current user.')
            ->assertJsonPath('errors.session_token.0', 'The provided session token belongs to another user.');
    }

    public function test_submit_score_keeps_the_higher_existing_best_score_when_new_score_is_lower(): void
    {
        $user = User::factory()->create([
            'best_score' => 500,
        ]);

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'lower-score-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->signedJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'lower-score-session',
            'score' => 300,
            'metadata' => ['duration' => 90],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.best_score', 500);

        $this->assertSame(500, $user->fresh()->best_score);
    }

    public function test_submit_score_without_collected_coins_keeps_coin_balance_unchanged(): void
    {
        $user = User::factory()->create([
            'best_score' => 100,
            'coins' => 25,
        ]);

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'no-coins-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->signedJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'no-coins-session',
            'score' => 300,
            'metadata' => ['duration' => 90],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.coins', 25);

        $this->assertSame(25, $user->fresh()->coins);
    }

    public function test_submit_score_rejects_unknown_metadata_fields(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'unknown-metadata-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->signedJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'unknown-metadata-session',
            'score' => 300,
            'metadata' => [
                'duration' => 90,
                'anti_fraud' => [
                    'client_score_hash' => 'abc123',
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonStructure([
                'errors' => ['metadata'],
            ]);
    }

    public function test_submit_score_rejects_negative_collected_coins(): void
    {
        $user = User::factory()->create([
            'coins' => 10,
        ]);

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'negative-coins-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->signedJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'negative-coins-session',
            'score' => 300,
            'metadata' => [
                'duration' => 90,
                'coins_collected' => -1,
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonValidationErrors(['metadata.coins_collected']);

        $this->assertSame(10, $user->fresh()->coins);
    }
}
