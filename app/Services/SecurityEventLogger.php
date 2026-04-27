<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SecurityEventLogger
{
    public function logSessionNotFound(User $user, string $sessionToken): void
    {
        $this->log('game_session_not_found', [
            'user_id' => $user->id,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
        ], 'Rejected score submission because the session token was not found.');
    }

    public function logSessionCloseNotFound(User $user, string $sessionToken): void
    {
        $this->log('game_session_close_not_found', [
            'user_id' => $user->id,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
        ], 'Rejected session close because the session token was not found.');
    }

    public function logForeignSessionUsage(User $user, string $sessionToken, int $ownerUserId): void
    {
        $this->log('game_session_foreign_user_attempt', [
            'user_id' => $user->id,
            'owner_user_id' => $ownerUserId,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
        ], 'Rejected score submission because the session belongs to another user.');
    }

    public function logForeignSessionCloseAttempt(User $user, string $sessionToken, int $ownerUserId): void
    {
        $this->log('game_session_close_foreign_user_attempt', [
            'user_id' => $user->id,
            'owner_user_id' => $ownerUserId,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
        ], 'Rejected session close because the session belongs to another user.');
    }

    public function logExpiredSessionUsage(User $user, string $sessionToken): void
    {
        $this->log('game_session_expired_attempt', [
            'user_id' => $user->id,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
        ], 'Rejected score submission because the session is expired.');
    }

    public function logInactiveSessionUsage(User $user, string $sessionToken, string $status): void
    {
        $this->log('game_session_inactive_attempt', [
            'user_id' => $user->id,
            'status' => $status,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
        ], 'Rejected score submission because the session is not active.');
    }

    public function logInactiveSessionCloseAttempt(User $user, string $sessionToken, string $status): void
    {
        $this->log('game_session_close_inactive_attempt', [
            'user_id' => $user->id,
            'status' => $status,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
        ], 'Rejected session close because the session is not active.');
    }

    public function logDuplicateSessionSubmission(User $user, string $sessionToken): void
    {
        $this->log('game_session_duplicate_submission_attempt', [
            'user_id' => $user->id,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
        ], 'Rejected score submission because the session was already submitted.');
    }

    /**
     * @param  array<string, array{expected: mixed, received: mixed}>  $mismatches
     */
    public function logSessionMetadataMismatch(User $user, string $sessionToken, array $mismatches): void
    {
        $this->log('game_session_metadata_mismatch', [
            'user_id' => $user->id,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
            'mismatches' => $mismatches,
        ], 'Rejected score submission because the session metadata did not match.');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logInvalidScoreSubmission(
        User $user,
        string $sessionToken,
        ?int $score,
        string $reason,
        array $context = [],
    ): void {
        $this->log('invalid_score_submission', array_merge([
            'reason' => $reason,
            'user_id' => $user->id,
            'score' => $score,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
        ], $context), 'Rejected score submission because the payload failed validation.');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logSuspiciousScoreSubmission(
        User $user,
        int $gameSessionId,
        int $score,
        array $context = [],
    ): void {
        $this->log('suspicious_score_submission', array_merge([
            'user_id' => $user->id,
            'game_session_id' => $gameSessionId,
            'score' => $score,
        ], $this->sanitizeContext($context)), 'Detected suspicious score submission.');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logRecalculatedSuspiciousScore(
        int $userId,
        int $gameScoreId,
        int $score,
        array $context = [],
    ): void {
        $this->log('recalculated_suspicious_score', array_merge([
            'user_id' => $userId,
            'game_score_id' => $gameScoreId,
            'score' => $score,
        ], $this->sanitizeContext($context)), 'Detected suspicious score during historical recalculation.');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logInvalidCollectedCoinsSubmission(
        User $user,
        string $sessionToken,
        int $coinsCollected,
        string $reason,
        array $context = [],
    ): void {
        $this->log('invalid_collected_coins_submission', array_merge([
            'reason' => $reason,
            'user_id' => $user->id,
            'coins_collected' => $coinsCollected,
            'session_token_fingerprint' => $this->fingerprint($sessionToken),
        ], $this->sanitizeContext($context)), 'Rejected score submission because the collected coin payload failed validation.');
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
                if (Str::contains($normalizedKey, ['secret'])) {
                    $sanitized[$key] = '[redacted]';
                } elseif (Str::contains($normalizedKey, ['token'])) {
                    $sanitized[$key] = $this->fingerprint($value);
                } else {
                    $sanitized[$key] = $value;
                }

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            }
        }

        return $sanitized;
    }

    /**
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    protected function sanitizeArray(array $value): array
    {
        $sanitized = [];

        foreach ($value as $key => $item) {
            $normalizedKey = is_string($key) ? Str::lower($key) : null;

            if ($item === null || is_bool($item) || is_int($item) || is_float($item)) {
                $sanitized[$key] = $item;

                continue;
            }

            if (is_string($item)) {
                if ($normalizedKey !== null && Str::contains($normalizedKey, ['secret'])) {
                    $sanitized[$key] = '[redacted]';
                } elseif ($normalizedKey !== null && Str::contains($normalizedKey, ['token'])) {
                    $sanitized[$key] = $this->fingerprint($item);
                } else {
                    $sanitized[$key] = $item;
                }

                continue;
            }

            if (is_array($item)) {
                $sanitized[$key] = $this->sanitizeArray($item);
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
