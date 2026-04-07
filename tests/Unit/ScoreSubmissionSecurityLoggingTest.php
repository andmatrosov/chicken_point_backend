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
}
