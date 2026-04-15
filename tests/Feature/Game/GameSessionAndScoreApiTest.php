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

    public function test_start_session_returns_a_session_token_and_persists_an_active_session(): void
    {
        $user = User::factory()->create();

        $response = $this->bearerJsonAsUser($user, 'POST', '/api/game/session/start', [
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
                'data' => ['session_token', 'status'],
                'meta',
            ])
            ->assertJsonPath('data.status', GameSessionStatus::ACTIVE->value);

        $sessionToken = $response->json('data.session_token');
        $gameSession = GameSession::query()->where('token', $sessionToken)->firstOrFail();

        $this->assertDatabaseHas('game_sessions', [
            'user_id' => $user->id,
            'token' => $sessionToken,
            'status' => GameSessionStatus::ACTIVE->value,
        ]);
        $this->assertNull($gameSession->expires_at);

        $this->assertSame(
            [
                'device_id' => 'ios-device-1',
                'platform' => 'ios',
                'app_version' => '1.0.0',
            ],
            $gameSession->metadata,
        );
    }

    public function test_start_session_rejects_unknown_metadata_fields(): void
    {
        $user = User::factory()->create();

        $this->bearerJsonAsUser($user, 'POST', '/api/game/session/start', [
            'metadata' => [
                'device_id' => 'ios-device-1',
                'platform' => 'ios',
                'app_version' => '1.0.0',
                'duration' => 120,
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('errors.metadata.0', 'The metadata field must not have any additional fields.');
    }

    public function test_start_session_cancels_the_previous_active_session(): void
    {
        $user = User::factory()->create();

        $previousSession = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'previous-active-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subHour(),
            'expires_at' => null,
            'metadata' => ['device_id' => 'ios-old'],
        ]);

        $response = $this->bearerJsonAsUser($user, 'POST', '/api/game/session/start', [
            'metadata' => [
                'device_id' => 'ios-new',
                'platform' => 'ios',
                'app_version' => '2.0.0',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', GameSessionStatus::ACTIVE->value);

        $this->assertSame(GameSessionStatus::CANCELED, $previousSession->fresh()->status);
        $this->assertDatabaseCount('game_sessions', 2);
    }

    public function test_close_session_cancels_the_active_session(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'closable-session-token',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subMinutes(30),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/session/close', [
            'session_token' => 'closable-session-token',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session_token', 'closable-session-token')
            ->assertJsonPath('data.status', GameSessionStatus::CANCELED->value);

        $this->assertDatabaseHas('game_sessions', [
            'token' => 'closable-session-token',
            'status' => GameSessionStatus::CANCELED->value,
        ]);
    }

    public function test_submit_score_creates_score_marks_session_submitted_and_updates_best_score_and_coins(): void
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
            'expires_at' => null,
            'metadata' => ['device_id' => 'ios-device-1'],
        ]);

        $this->assertTrue($session->id > 0);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'submit-score-session',
            'score' => 420,
            'coins_collected' => 17,
            'metadata' => [
                'duration' => 120,
                'device_id' => 'ios-device-1',
                'app_version' => '1.0.0',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.best_score', 420)
            ->assertJsonPath('data.coins', 27)
            ->assertJsonPath('data.current_rank', 1);

        $this->assertDatabaseHas('game_scores', [
            'user_id' => $user->id,
            'score' => 420,
            'coins_collected' => 17,
            'session_token' => 'submit-score-session',
            'is_processed' => true,
        ]);
        $this->assertDatabaseHas('game_sessions', [
            'id' => $session->id,
            'status' => GameSessionStatus::SUBMITTED->value,
        ]);
        $this->assertSame(420, $user->fresh()->best_score);
        $this->assertSame(27, $user->fresh()->coins);
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
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'duplicate-session-token',
            'score' => 200,
            'coins_collected' => 3,
            'metadata' => [
                'duration' => 60,
            ],
        ])
            ->assertOk();

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'duplicate-session-token',
            'score' => 210,
            'coins_collected' => 4,
            'metadata' => ['duration' => 70],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This session has already been submitted.')
            ->assertJsonPath('errors.session_token.0', 'The provided session token has already been used.');

        $this->assertSame(8, $user->fresh()->coins);
    }

    public function test_submit_score_accepts_long_running_active_sessions_without_ttl(): void
    {
        $user = User::factory()->create([
            'best_score' => 50,
            'coins' => 2,
        ]);

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'long-running-session-token',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subHours(4),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'long-running-session-token',
            'score' => 300,
            'coins_collected' => 5,
            'metadata' => ['duration' => 100],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.best_score', 300)
            ->assertJsonPath('data.coins', 7);
    }

    public function test_submit_score_rejects_canceled_sessions(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'canceled-session-token',
            'status' => GameSessionStatus::CANCELED,
            'issued_at' => now()->subMinutes(20),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'canceled-session-token',
            'score' => 300,
            'coins_collected' => 0,
            'metadata' => ['duration' => 100],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This session is not available for score submission.')
            ->assertJsonPath('errors.session_token.0', 'The provided session token is not active.');
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
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($intruder, 'POST', '/api/game/submit-score', [
            'session_token' => 'foreign-session-token',
            'score' => 250,
            'coins_collected' => 0,
            'metadata' => ['duration' => 80],
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This session does not belong to the current user.')
            ->assertJsonPath('errors.session_token.0', 'The provided session token belongs to another user.');
    }

    public function test_submit_score_rejects_session_metadata_mismatch(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'metadata-mismatch-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => null,
            'metadata' => [
                'device_id' => 'ios-device-1',
                'platform' => 'ios',
                'app_version' => '1.0.0',
            ],
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'metadata-mismatch-session',
            'score' => 250,
            'coins_collected' => 6,
            'metadata' => [
                'duration' => 80,
                'device_id' => 'android-device-1',
                'platform' => 'ios',
                'app_version' => '1.0.0',
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The provided session metadata does not match the issued session.')
            ->assertJsonFragment([
                'The provided device_id does not match the issued game session.',
            ]);

        $this->assertDatabaseMissing('game_scores', [
            'session_token' => 'metadata-mismatch-session',
        ]);
        $this->assertDatabaseHas('game_sessions', [
            'token' => 'metadata-mismatch-session',
            'status' => GameSessionStatus::ACTIVE->value,
        ]);
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
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'lower-score-session',
            'score' => 300,
            'coins_collected' => 0,
            'metadata' => ['duration' => 90],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.best_score', 500);

        $this->assertSame(500, $user->fresh()->best_score);
    }

    public function test_submit_score_with_zero_collected_coins_keeps_coin_balance_unchanged(): void
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
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'no-coins-session',
            'score' => 300,
            'coins_collected' => 0,
            'metadata' => ['duration' => 90],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.coins', 25);

        $this->assertSame(25, $user->fresh()->coins);
        $this->assertDatabaseHas('game_scores', [
            'user_id' => $user->id,
            'score' => 300,
            'coins_collected' => 0,
            'session_token' => 'no-coins-session',
            'is_processed' => true,
        ]);
    }

    public function test_submit_score_rejects_scores_above_the_allowed_range(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'out-of-range-score-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'out-of-range-score-session',
            'score' => (int) config('game.score_validation.max_score') + 1,
            'coins_collected' => 0,
            'metadata' => ['duration' => 90],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The submitted score is outside the allowed range.')
            ->assertJsonPath('errors.score.0', 'The provided score is invalid.');

        $this->assertDatabaseMissing('game_scores', [
            'session_token' => 'out-of-range-score-session',
        ]);
        $this->assertDatabaseHas('game_sessions', [
            'token' => 'out-of-range-score-session',
            'status' => GameSessionStatus::ACTIVE->value,
        ]);
    }

    public function test_submit_score_rejects_client_controlled_coin_metadata(): void
    {
        $user = User::factory()->create([
            'coins' => 25,
        ]);

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'client-coins-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'client-coins-session',
            'score' => 300,
            'coins_collected' => 12,
            'metadata' => [
                'duration' => 90,
                'coins_collected' => 999999,
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('errors.metadata.0', 'The metadata field must not have any additional fields.');

        $this->assertSame(25, $user->fresh()->coins);
        $this->assertDatabaseMissing('game_scores', [
            'session_token' => 'client-coins-session',
        ]);
        $this->assertDatabaseHas('game_sessions', [
            'token' => 'client-coins-session',
            'status' => GameSessionStatus::ACTIVE->value,
        ]);
    }

    public function test_submit_score_rejects_unknown_metadata_fields(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'unknown-metadata-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'unknown-metadata-session',
            'score' => 300,
            'coins_collected' => 0,
            'metadata' => [
                'duration' => 90,
                'anti_fraud' => [
                    'client_score_hash' => 'abc123',
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
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
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'negative-coins-session',
            'score' => 300,
            'coins_collected' => -1,
            'metadata' => ['duration' => 90],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('errors.coins_collected.0', 'The coins collected field must be at least 0.');

        $this->assertSame(10, $user->fresh()->coins);
    }

    public function test_submit_score_rejects_collected_coins_above_the_allowed_range(): void
    {
        $user = User::factory()->create([
            'coins' => 10,
        ]);

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'too-many-coins-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'too-many-coins-session',
            'score' => 300,
            'coins_collected' => (int) config('game.score_validation.max_coins_collected_per_run') + 1,
            'metadata' => ['duration' => 90],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The submitted collected coin value is outside the allowed range.')
            ->assertJsonPath('errors.coins_collected.0', 'The provided coins_collected value is invalid.');

        $this->assertSame(10, $user->fresh()->coins);
        $this->assertDatabaseMissing('game_scores', [
            'session_token' => 'too-many-coins-session',
        ]);
        $this->assertDatabaseHas('game_sessions', [
            'token' => 'too-many-coins-session',
            'status' => GameSessionStatus::ACTIVE->value,
        ]);
    }
}
