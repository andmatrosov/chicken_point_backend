<?php

namespace Tests\Unit;

use App\Services\SecurityEventLogger;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SecurityEventLoggerTest extends TestCase
{
    public function test_it_redacts_and_fingerprints_sensitive_business_failure_context(): void
    {
        Log::spy();

        app(SecurityEventLogger::class)->logBusinessFailure('auto_prize_assignment_failed', [
            'session_token' => 'session-token-value',
            'secret' => 'secret-value',
            'metadata' => ['duration' => 42, 'app_version' => '1.0.0'],
            'nested' => [
                'access_token' => 'access-token-value',
            ],
            'user_id' => 15,
        ]);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Business failure detected.'
                    && $context['event'] === 'business_failure'
                    && $context['failure_event'] === 'auto_prize_assignment_failed'
                    && $context['session_token'] !== 'session-token-value'
                    && $context['secret'] === '[redacted]'
                    && $context['metadata'] === ['duration' => 42, 'app_version' => '1.0.0']
                    && is_array($context['nested'])
                    && $context['nested']['access_token'] !== 'access-token-value'
                    && $context['user_id'] === 15;
            });
    }

    public function test_it_logs_invalid_collected_coins_with_structured_context(): void
    {
        Log::spy();

        $user = new \App\Models\User();
        $user->id = 42;

        app(SecurityEventLogger::class)->logInvalidCollectedCoinsSubmission(
            $user,
            'session-token-value',
            9999,
            'coins_out_of_range',
            [
                'score' => 2500,
                'duration' => 120,
                'max_coins_collected_per_run' => 1000,
                'secret_hint' => 'sensitive',
            ],
        );

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Rejected score submission because the collected coin payload failed validation.'
                    && $context['event'] === 'invalid_collected_coins_submission'
                    && $context['reason'] === 'coins_out_of_range'
                    && $context['user_id'] === 42
                    && $context['coins_collected'] === 9999
                    && $context['score'] === 2500
                    && $context['duration'] === 120
                    && $context['max_coins_collected_per_run'] === 1000
                    && $context['session_token_fingerprint'] !== 'session-token-value'
                    && $context['secret_hint'] === '[redacted]';
            });
    }
}
