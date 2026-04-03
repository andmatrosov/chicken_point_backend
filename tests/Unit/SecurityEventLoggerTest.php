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
            'nonce' => 'nonce-value',
            'signature' => 'signature-value',
            'secret' => 'secret-value',
            'metadata' => ['duration' => 42, 'app_version' => '1.0.0'],
            'user_id' => 15,
        ]);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Business failure detected.'
                    && $context['event'] === 'business_failure'
                    && $context['failure_event'] === 'auto_prize_assignment_failed'
                    && $context['session_token'] !== 'session-token-value'
                    && $context['nonce'] !== 'nonce-value'
                    && $context['signature'] === '[redacted]'
                    && $context['secret'] === '[redacted]'
                    && $context['metadata'] === ['duration', 'app_version']
                    && $context['user_id'] === 15;
            });
    }
}
