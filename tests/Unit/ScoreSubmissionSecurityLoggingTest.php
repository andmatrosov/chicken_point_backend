<?php

namespace Tests\Unit;

use App\Data\Game\ScoreSuspicionResult;
use App\Enums\GameSessionStatus;
use App\Exceptions\BusinessException;
use App\Models\GameSession;
use App\Models\User;
use App\Services\ScoreSubmissionService;
use App\Services\SecurityEventLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ScoreSubmissionSecurityLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_session_attempt_is_logged(): void
    {
        $user = User::factory()->create();

        $this->mock(SecurityEventLogger::class, function (MockInterface $mock): void {
            $mock->shouldReceive('logSessionNotFound')
                ->once()
                ->withArgs(fn (User $loggedUser, string $sessionToken): bool => $sessionToken === 'invalid-session-token' && $loggedUser->exists);
        });

        $this->expectException(BusinessException::class);

        app(ScoreSubmissionService::class)->lockSessionForSubmission(
            $user,
            'invalid-session-token',
        );
    }

    public function test_out_of_range_score_submission_is_logged(): void
    {
        $user = User::factory()->create();

        $this->mock(SecurityEventLogger::class, function (MockInterface $mock): void {
            $mock->shouldReceive('logInvalidScoreSubmission')
                ->once()
                ->withArgs(function (
                    User $loggedUser,
                    string $sessionToken,
                    ?int $score,
                    string $reason,
                    array $context,
                ): bool {
                    return $reason === 'score_out_of_range'
                        && $score === 1000001
                        && $sessionToken === 'suspicious-session-token'
                        && $loggedUser->exists
                        && array_key_exists('min_score', $context)
                        && array_key_exists('max_score', $context);
                });
        });

        $this->expectException(BusinessException::class);

        app(ScoreSubmissionService::class)->validateScore(
            $user,
            'suspicious-session-token',
            1000001,
        );
    }

    public function test_adaptive_hard_suspicious_detection_marks_result_as_hard(): void
    {
        $gameSession = GameSession::query()->create([
            'user_id' => User::factory()->create()->id,
            'token' => 'adaptive-hard-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subMinutes(4),
            'expires_at' => null,
        ]);

        $result = app(ScoreSubmissionService::class)->detectSuspiciousScoreSubmission(
            $gameSession,
            700,
        );

        $this->assertInstanceOf(ScoreSuspicionResult::class, $result);
        $this->assertTrue($result->isSuspicious);
        $this->assertTrue($result->isHardSuspicious);
        $this->assertSame(3, $result->points);
        $this->assertSame('adaptive_score_limit_exceeded', $result->reason);
        $this->assertSame(240, $result->context['elapsed_seconds']);
        $this->assertSame(600, $result->context['adaptive_max_score']);
        $this->assertSame(['adaptive_score_limit_exceeded'], array_column($result->signals, 'reason'));
    }

    public function test_soft_suspicious_detection_returns_single_point_signal(): void
    {
        $gameSession = GameSession::query()->create([
            'user_id' => User::factory()->create()->id,
            'token' => 'soft-suspicious-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSeconds(20),
            'expires_at' => null,
        ]);

        $result = app(ScoreSubmissionService::class)->detectSuspiciousScoreSubmission(
            $gameSession,
            80,
        );

        $this->assertTrue($result->isSuspicious);
        $this->assertFalse($result->isHardSuspicious);
        $this->assertSame(1, $result->points);
        $this->assertSame('high_score_velocity', $result->reason);
        $this->assertSame(['high_score_velocity'], array_column($result->signals, 'reason'));
    }

    public function test_duration_mismatch_is_diagnostic_only(): void
    {
        $gameSession = GameSession::query()->create([
            'user_id' => User::factory()->create()->id,
            'token' => 'duration-mismatch-session',
            'status' => GameSessionStatus::SUBMITTED,
            'issued_at' => now()->subSeconds(60),
            'submitted_at' => now(),
            'expires_at' => null,
            'metadata' => [
                'submission' => [
                    'duration' => 120,
                ],
            ],
        ]);

        $result = app(ScoreSubmissionService::class)->detectSuspiciousScoreSubmission(
            $gameSession,
            30,
            $gameSession->submitted_at,
        );

        $this->assertTrue($result->isSuspicious);
        $this->assertFalse($result->isHardSuspicious);
        $this->assertSame(0, $result->points);
        $this->assertSame('duration_mismatch', $result->reason);
        $this->assertSame(['duration_mismatch'], array_column($result->signals, 'reason'));
        $this->assertFalse((bool) ($result->signals[0]['counts_for_points'] ?? true));
    }

    public function test_velocity_and_duration_mismatch_signals_are_combined(): void
    {
        $gameSession = GameSession::query()->create([
            'user_id' => User::factory()->create()->id,
            'token' => 'combined-signals-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSeconds(20),
            'expires_at' => null,
        ]);

        $result = app(ScoreSubmissionService::class)->detectSuspiciousScoreSubmission(
            $gameSession,
            80,
            now(),
            ['duration' => 120],
        );

        $this->assertTrue($result->isSuspicious);
        $this->assertFalse($result->isHardSuspicious);
        $this->assertSame(1, $result->points);
        $this->assertSame(['high_score_velocity', 'duration_mismatch'], array_column($result->signals, 'reason'));
    }

    public function test_unreliable_server_duration_blocks_cheat_detection(): void
    {
        $gameSession = GameSession::query()->create([
            'user_id' => User::factory()->create()->id,
            'token' => 'unreliable-duration-session',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subSecond(),
            'expires_at' => null,
        ]);

        $result = app(ScoreSubmissionService::class)->detectSuspiciousScoreSubmission(
            $gameSession,
            398,
            now(),
            ['duration' => 300],
        );

        $this->assertTrue($result->isSuspicious);
        $this->assertFalse($result->isHardSuspicious);
        $this->assertSame(0, $result->points);
        $this->assertSame(['unreliable_server_duration', 'duration_mismatch'], array_column($result->signals, 'reason'));
        $this->assertSame([], $result->context['points_bearing_reasons']);
        $this->assertTrue($result->context['cheat_detection_skipped_due_to_unreliable_timing']);
    }
}
