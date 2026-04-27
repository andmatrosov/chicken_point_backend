<?php

namespace Tests\Unit;

use App\Data\Game\ScoreSuspicionResult;
use App\Exceptions\BusinessException;
use App\Enums\GameSessionStatus;
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
            1016,
        );

        $this->assertInstanceOf(ScoreSuspicionResult::class, $result);
        $this->assertTrue($result->isSuspicious);
        $this->assertTrue($result->isHardSuspicious);
        $this->assertSame(3, $result->points);
        $this->assertSame('adaptive_score_limit_exceeded', $result->reason);
        $this->assertSame(240, $result->context['elapsed_seconds']);
        $this->assertSame(600, $result->context['adaptive_max_score']);
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
    }
}
