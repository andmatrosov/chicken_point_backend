<?php

namespace Tests\Unit;

use App\Enums\GameSessionStatus;
use App\Models\GameScore;
use App\Models\GameSession;
use App\Models\User;
use App\Models\UserSuspiciousEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameScoreAntiCheatPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_timing_only_unreliable_duration_is_not_critical(): void
    {
        $user = User::factory()->create();

        $session = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'timing-only-session',
            'status' => GameSessionStatus::SUBMITTED,
            'issued_at' => now()->subSecond(),
            'submitted_at' => now(),
            'expires_at' => null,
            'metadata' => [
                'submission' => [
                    'duration' => 300,
                ],
            ],
        ]);

        $score = GameScore::query()->create([
            'user_id' => $user->id,
            'score' => 398,
            'coins_collected' => 0,
            'session_token' => $session->token,
            'is_processed' => true,
        ]);

        UserSuspiciousEvent::query()->create([
            'user_id' => $user->id,
            'game_score_id' => $score->id,
            'reason' => 'unreliable_server_duration',
            'points' => 0,
            'signals' => [
                ['reason' => 'unreliable_server_duration', 'points' => 0, 'level' => 'diagnostic', 'counts_for_points' => false, 'category' => 'timing'],
                ['reason' => 'duration_mismatch', 'points' => 0, 'level' => 'soft', 'counts_for_points' => false, 'category' => 'timing'],
            ],
            'context' => [
                'duration_reliability' => 'unreliable',
            ],
        ]);

        $score->load(['gameSession', 'suspiciousEvent']);

        $this->assertSame('timing_only', $score->suspiciousStatus());
        $this->assertFalse($score->hasPointsBearingCheatSignal());
    }

    public function test_persisted_reliability_takes_priority_over_runtime_recomputation(): void
    {
        $user = User::factory()->create();

        $session = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'persisted-reliability-session',
            'status' => GameSessionStatus::SUBMITTED,
            'issued_at' => now()->subSecond(),
            'submitted_at' => now(),
            'expires_at' => null,
            'metadata' => [
                'submission' => [
                    'duration' => 300,
                ],
            ],
        ]);

        $score = GameScore::query()->create([
            'user_id' => $user->id,
            'score' => 398,
            'coins_collected' => 0,
            'session_token' => $session->token,
            'is_processed' => true,
        ]);

        UserSuspiciousEvent::query()->create([
            'user_id' => $user->id,
            'game_score_id' => $score->id,
            'reason' => 'duration_mismatch',
            'points' => 0,
            'signals' => [
                ['reason' => 'duration_mismatch', 'points' => 0, 'level' => 'soft', 'counts_for_points' => false, 'category' => 'timing'],
            ],
            'context' => [
                'duration_reliability' => 'reliable',
            ],
        ]);

        $score->load(['gameSession', 'suspiciousEvent']);

        $this->assertSame('reliable', $score->persistedDurationReliabilityStatus());
        $this->assertSame('unreliable', $score->runtimeDurationReliabilityStatus());
        $this->assertSame('reliable', $score->durationReliabilityStatus());
        $this->assertTrue($score->isServerDurationReliable());
    }

    public function test_cheat_signal_with_points_still_keeps_danger_classification(): void
    {
        $user = User::factory()->create();

        $session = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'cheat-danger-session',
            'status' => GameSessionStatus::SUBMITTED,
            'issued_at' => now()->subSeconds(20),
            'submitted_at' => now(),
            'expires_at' => null,
        ]);

        $score = GameScore::query()->create([
            'user_id' => $user->id,
            'score' => 500,
            'coins_collected' => 0,
            'session_token' => $session->token,
            'is_processed' => true,
        ]);

        UserSuspiciousEvent::query()->create([
            'user_id' => $user->id,
            'game_score_id' => $score->id,
            'reason' => 'adaptive_score_limit_exceeded',
            'points' => 3,
            'signals' => [
                ['reason' => 'adaptive_score_limit_exceeded', 'points' => 3, 'level' => 'hard', 'counts_for_points' => true, 'category' => 'cheat'],
            ],
            'context' => [
                'duration_reliability' => 'reliable',
            ],
        ]);

        $score->load(['gameSession', 'suspiciousEvent']);

        $this->assertSame('critical', $score->suspiciousStatus());
        $this->assertTrue($score->hasPointsBearingCheatSignal());
    }
}
