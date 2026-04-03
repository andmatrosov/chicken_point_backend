<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SecurityEventLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function logSignatureFailure(Request $request, string $reason, array $context = []): void
    {
        $this->log('signature_validation_failed', array_merge([
            'reason' => $reason,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'timestamp_header' => $request->header((string) config('game.signature.headers.timestamp')),
            'nonce_fingerprint' => $this->fingerprint(
                (string) $request->header((string) config('game.signature.headers.nonce')),
            ),
        ], $this->sanitizeContext($context)));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logNonceReplay(Request $request, string $nonce, array $context = []): void
    {
        $this->log('request_nonce_replayed', array_merge([
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'nonce_fingerprint' => $this->fingerprint($nonce),
            'timestamp_header' => $request->header((string) config('game.signature.headers.timestamp')),
        ], $this->sanitizeContext($context)));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logScoreSubmissionRejection(
        User $user,
        string $sessionToken,
        ?int $score,
        string $reason,
        array $context = [],
    ): void {
        $this->log('score_submission_rejected', array_merge([
            'reason' => $reason,
            'user_id' => $user->id,
            'score' => $score,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
        ], $this->sanitizeContext($context)));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logBusinessFailure(string $failureEvent, array $context = []): void
    {
        $this->log(
            'business_failure',
            array_merge([
                'failure_event' => $failureEvent,
            ], $this->sanitizeContext($context)),
            'Business failure detected.',
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function log(string $event, array $context, string $message = 'Security event detected.'): void
    {
        Log::warning($message, array_merge([
            'event' => $event,
        ], $context));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function sanitizeContext(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $normalizedKey = Str::lower((string) $key);

            if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
                $sanitized[$key] = $value;

                continue;
            }

            if (is_string($value)) {
                if (Str::contains($normalizedKey, ['signature', 'secret'])) {
                    $sanitized[$key] = '[redacted]';
                } elseif (Str::contains($normalizedKey, ['token', 'nonce'])) {
                    $sanitized[$key] = $this->fingerprint($value);
                } else {
                    $sanitized[$key] = $value;
                }

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = array_is_list($value) ? $value : array_keys($value);
            }
        }

        return $sanitized;
    }

    protected function fingerprint(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr(hash('sha256', $value), 0, 16);
    }
}
