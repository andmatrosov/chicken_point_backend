<?php

namespace Tests\Unit;

use App\Exceptions\BusinessException;
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
            $mock->shouldReceive('logScoreSubmissionRejection')
                ->once()
                ->withArgs(fn (User $loggedUser, string $sessionToken, ?int $score, string $reason): bool => $reason === 'missing_session' && $score === null && $sessionToken === 'invalid-session-token' && $loggedUser->exists);
        });

        $this->expectException(BusinessException::class);

        app(ScoreSubmissionService::class)->validateSessionOwnershipAndState(
            $user,
            'invalid-session-token',
            null,
        );
    }

    public function test_suspicious_score_pattern_is_logged(): void
    {
        $user = User::factory()->create();

        $this->mock(SecurityEventLogger::class, function (MockInterface $mock): void {
            $mock->shouldReceive('logScoreSubmissionRejection')
                ->once()
                ->withArgs(fn (User $loggedUser, string $sessionToken, ?int $score, string $reason): bool => $reason === 'score_out_of_range' && $score === 1000001 && $sessionToken === 'suspicious-session-token' && $loggedUser->exists);
        });

        $this->expectException(BusinessException::class);

        app(ScoreSubmissionService::class)->validateScore(
            $user,
            'suspicious-session-token',
            1000001,
        );
    }
}
