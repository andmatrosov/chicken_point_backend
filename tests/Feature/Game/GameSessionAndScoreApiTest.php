<?php

namespace Tests\Feature\Game;

use App\Enums\GameSessionStatus;
use App\Models\GameSession;
use App\Models\User;
use App\Models\UserSuspiciousEvent;
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
            'issued_at' => now()->subMinutes(2),
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
        $this->assertSame(0, $user->fresh()->suspicious_game_result_points);
        $this->assertFalse((bool) $user->fresh()->has_suspicious_game_results);
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
            'issued_at' => now()->subMinute(),
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
            'issued_at' => now()->subMinutes(2),
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
            'issued_at' => now()->subMinutes(2),
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
            'issued_at' => now()->subMinutes(2),
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

    public function test_submit_score_soft_suspicious_adds_one_point_without_flagging_user(): void
    {
        $user = User::factory()->create([
            'best_score' => 100,
            'coins' => 25,
        ]);

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'soft-suspicious-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSeconds(20),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'soft-suspicious-session',
            'score' => 80,
            'coins_collected' => 10,
            'metadata' => ['duration' => 20],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.best_score', 100)
            ->assertJsonPath('data.coins', 35)
            ->assertJsonPath('data.current_rank', 1);

        $this->assertSame(100, $user->fresh()->best_score);
        $this->assertSame(35, $user->fresh()->coins);
        $this->assertSame(1, $user->fresh()->suspicious_game_result_points);
        $this->assertFalse((bool) $user->fresh()->has_suspicious_game_results);
        $this->assertNull($user->fresh()->suspicious_game_results_flagged_at);
        $this->assertDatabaseHas('game_scores', [
            'session_token' => 'soft-suspicious-session',
            'score' => 80,
            'coins_collected' => 10,
        ]);
        $this->assertDatabaseHas('user_suspicious_events', [
            'user_id' => $user->id,
            'reason' => 'high_score_velocity',
            'points' => 1,
        ]);
    }

    public function test_three_soft_suspicious_submissions_flag_the_user(): void
    {
        $user = User::factory()->create();

        foreach (['soft-session-1', 'soft-session-2', 'soft-session-3'] as $token) {
            GameSession::query()->create([
                'user_id' => $user->id,
                'token' => $token,
                'status' => GameSessionStatus::ACTIVE,
                'issued_at' => now()->subSeconds(20),
                'expires_at' => null,
            ]);

            $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
                'session_token' => $token,
                'score' => 80,
                'coins_collected' => 0,
                'metadata' => ['duration' => 20],
            ])
                ->assertOk()
                ->assertJsonPath('success', true);
        }

        $this->assertTrue((bool) $user->fresh()->has_suspicious_game_results);
        $this->assertSame(3, $user->fresh()->suspicious_game_result_points);
        $this->assertSame('high_score_velocity', $user->fresh()->suspicious_game_results_reason);
    }

    public function test_submit_score_hard_suspicious_saves_result_and_flags_the_user_immediately(): void
    {
        $user = User::factory()->create([
            'best_score' => 100,
            'coins' => 25,
        ]);

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'hard-suspicious-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subMinutes(4),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'hard-suspicious-session',
            'score' => 700,
            'coins_collected' => 10,
            'metadata' => ['duration' => 240],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.best_score', 700)
            ->assertJsonPath('data.coins', 35)
            ->assertJsonPath('data.current_rank', null);

        $this->assertSame(3, $user->fresh()->suspicious_game_result_points);
        $this->assertTrue((bool) $user->fresh()->has_suspicious_game_results);
        $this->assertSame('adaptive_score_limit_exceeded', $user->fresh()->suspicious_game_results_reason);
    }

    public function test_client_duration_does_not_change_server_based_score_detection(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'server-duration-hard-signal-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSeconds(15),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'server-duration-hard-signal-session',
            'score' => 1016,
            'coins_collected' => 0,
            'metadata' => ['duration' => 240],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_rank', null);

        $this->assertTrue((bool) $user->fresh()->has_suspicious_game_results);
        $this->assertSame(4, $user->fresh()->suspicious_game_result_points);
    }

    public function test_short_session_with_small_score_and_high_velocity_does_not_add_soft_points(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'small-short-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSeconds(5),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'small-short-session',
            'score' => 20,
            'coins_collected' => 0,
            'metadata' => ['duration' => 5],
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, $user->fresh()->suspicious_game_result_points);
        $this->assertFalse((bool) $user->fresh()->has_suspicious_game_results);
    }

    public function test_unreliable_server_duration_creates_only_timing_signals_without_points(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'unreliable-runtime-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSecond(),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'unreliable-runtime-session',
            'score' => 398,
            'coins_collected' => 0,
            'metadata' => ['duration' => 300],
        ])->assertOk();

        $this->assertSame(0, $user->fresh()->suspicious_game_result_points);
        $this->assertFalse((bool) $user->fresh()->has_suspicious_game_results);

        $event = UserSuspiciousEvent::query()->firstOrFail();
        $this->assertSame(0, $event->points);
        $this->assertSame(
            ['unreliable_server_duration', 'duration_mismatch'],
            array_column($event->signals, 'reason'),
        );
    }

    public function test_matching_duration_does_not_add_mismatch_points(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'duration-match-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSeconds(60),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'duration-match-session',
            'score' => 30,
            'coins_collected' => 0,
            'metadata' => ['duration' => 60],
        ])->assertOk();

        $this->assertSame(0, $user->fresh()->suspicious_game_result_points);
        $this->assertDatabaseCount('user_suspicious_events', 0);
    }

    public function test_duration_difference_within_grace_does_not_add_points(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'duration-grace-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSeconds(60),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'duration-grace-session',
            'score' => 30,
            'coins_collected' => 0,
            'metadata' => ['duration' => 64],
        ])->assertOk();

        $this->assertSame(0, $user->fresh()->suspicious_game_result_points);
        $this->assertDatabaseCount('user_suspicious_events', 0);
    }

    public function test_duration_difference_beyond_grace_logs_diagnostic_signal_without_points(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'duration-mismatch-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSeconds(60),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'duration-mismatch-session',
            'score' => 30,
            'coins_collected' => 0,
            'metadata' => ['duration' => 120],
        ])->assertOk();

        $this->assertSame(0, $user->fresh()->suspicious_game_result_points);
        $this->assertFalse((bool) $user->fresh()->has_suspicious_game_results);
        $this->assertDatabaseHas('user_suspicious_events', [
            'user_id' => $user->id,
            'reason' => 'duration_mismatch',
            'points' => 0,
        ]);
    }

    public function test_duration_mismatch_and_velocity_keep_only_cheat_points(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'velocity-and-duration-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSeconds(20),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'velocity-and-duration-session',
            'score' => 80,
            'coins_collected' => 0,
            'metadata' => ['duration' => 120],
        ])->assertOk();

        $this->assertSame(1, $user->fresh()->suspicious_game_result_points);
        $event = UserSuspiciousEvent::query()->firstOrFail();
        $this->assertCount(2, $event->signals);
        $this->assertSame(['high_score_velocity', 'duration_mismatch'], array_column($event->signals, 'reason'));
    }

    public function test_missing_duration_does_not_add_duration_mismatch_points(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'missing-duration-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSeconds(20),
            'expires_at' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/submit-score', [
            'session_token' => 'missing-duration-session',
            'score' => 80,
            'coins_collected' => 0,
            'metadata' => [],
        ])->assertOk();

        $this->assertSame(1, $user->fresh()->suspicious_game_result_points);
        $event = UserSuspiciousEvent::query()->firstOrFail();
        $this->assertSame(null, $event->context['client_duration']);
        $this->assertSame(['high_score_velocity'], array_column($event->signals, 'reason'));
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
            'issued_at' => now()->subMinutes(2),
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
