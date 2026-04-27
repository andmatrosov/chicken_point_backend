<?php

namespace App\Services;

use App\Data\Game\ScoreSuspicionResult;
use App\Enums\GameSessionStatus;
use App\Exceptions\BusinessException;
use App\Models\GameScore;
use App\Models\GameSession;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class ScoreSubmissionService
{
    public function __construct(
        protected GameSessionService $gameSessionService,
        protected SecurityEventLogger $securityEventLogger,
    ) {}

    public function lockSessionForSubmission(User $user, string $sessionToken): GameSession
    {
        $gameSession = GameSession::query()
            ->where('token', $sessionToken)
            ->lockForUpdate()
            ->first();

        if ($gameSession === null) {
            $this->securityEventLogger->logSessionNotFound($user, $sessionToken);

            throw new BusinessException(
                'Invalid game session.',
                errors: ['session_token' => ['The provided session token is invalid.']],
            );
        }

        if ($gameSession->user_id !== $user->id) {
            $this->securityEventLogger->logForeignSessionUsage($user, $sessionToken, $gameSession->user_id);

            throw new BusinessException(
                'This session does not belong to the current user.',
                403,
                ['session_token' => ['The provided session token belongs to another user.']],
            );
        }

        if ($gameSession->status === GameSessionStatus::SUBMITTED) {
            $this->securityEventLogger->logDuplicateSessionSubmission($user, $sessionToken);

            throw new BusinessException(
                'This session has already been submitted.',
                errors: ['session_token' => ['The provided session token has already been used.']],
            );
        }

        if ($gameSession->status === GameSessionStatus::EXPIRED) {
            $this->securityEventLogger->logExpiredSessionUsage($user, $sessionToken);

            throw new BusinessException(
                'This session has expired.',
                errors: ['session_token' => ['The provided session token has expired.']],
            );
        }

        if ($gameSession->status !== GameSessionStatus::ACTIVE) {
            $this->securityEventLogger->logInactiveSessionUsage(
                $user,
                $sessionToken,
                $gameSession->status->value,
            );

            throw new BusinessException(
                'This session is not available for score submission.',
                errors: ['session_token' => ['The provided session token is not active.']],
            );
        }

        return $gameSession;
    }

    public function validateScore(User $user, string $sessionToken, int $score): void
    {
        $minScore = (int) config('game.score_validation.min_score', 0);
        $maxScore = (int) config('game.score_validation.max_score', 1000000);

        if ($score < $minScore || $score > $maxScore) {
            $this->securityEventLogger->logInvalidScoreSubmission(
                $user,
                $sessionToken,
                $score,
                'score_out_of_range',
                [
                    'min_score' => $minScore,
                    'max_score' => $maxScore,
                ],
            );

            throw new BusinessException(
                'The submitted score is outside the allowed range.',
                errors: ['score' => ['The provided score is invalid.']],
            );
        }
    }

    public function detectSuspiciousScoreSubmission(
        GameSession $gameSession,
        int $score,
        ?CarbonInterface $submittedAt = null,
    ): ScoreSuspicionResult
    {
        if (! $this->isAntiCheatDetectionEnabled() || $score <= 0) {
            return ScoreSuspicionResult::clean();
        }

        $submittedAt ??= now();
        $elapsedSeconds = $this->calculateServerElapsedSeconds($gameSession, $submittedAt);
        $adaptiveMaxScore = $this->getAdaptiveMaxScoreForElapsed($elapsedSeconds);
        $scorePerSecond = $elapsedSeconds > 0 ? round($score / $elapsedSeconds, 4) : (float) $score;

        if ($adaptiveMaxScore !== null && $score >= $adaptiveMaxScore) {
            return new ScoreSuspicionResult(
                isSuspicious: true,
                isHardSuspicious: true,
                points: 3,
                reason: 'adaptive_score_limit_exceeded',
                context: [
                    'elapsed_seconds' => $elapsedSeconds,
                    'score_per_second' => $scorePerSecond,
                    'adaptive_max_score' => $adaptiveMaxScore,
                    'soft_threshold' => $this->softScoreVelocityThreshold(),
                    'server_issued_at' => $gameSession->issued_at?->toIso8601String(),
                    'submitted_at' => $submittedAt->toIso8601String(),
                ],
            );
        }

        if ($score >= $this->softScoreMinimum() && $scorePerSecond >= $this->softScoreVelocityThreshold()) {
            return new ScoreSuspicionResult(
                isSuspicious: true,
                isHardSuspicious: false,
                points: 1,
                reason: 'high_score_velocity',
                context: [
                    'elapsed_seconds' => $elapsedSeconds,
                    'score_per_second' => $scorePerSecond,
                    'adaptive_max_score' => $adaptiveMaxScore,
                    'soft_threshold' => $this->softScoreVelocityThreshold(),
                    'server_issued_at' => $gameSession->issued_at?->toIso8601String(),
                    'submitted_at' => $submittedAt->toIso8601String(),
                ],
            );
        }

        return ScoreSuspicionResult::clean();
    }

    public function calculateServerElapsedSeconds(
        GameSession $gameSession,
        ?CarbonInterface $submittedAt = null,
    ): int
    {
        $submittedAt ??= now();

        return max(0, $gameSession->issued_at->diffInSeconds($submittedAt));
    }

    public function getAdaptiveMaxScoreForElapsed(int $elapsedSeconds): ?int
    {
        $limits = config('game.anti_cheat.adaptive_score_limits', []);

        if (! is_array($limits)) {
            return null;
        }

        foreach ($limits as $limit) {
            if (! is_array($limit)) {
                continue;
            }

            $minSeconds = (int) ($limit['min_seconds'] ?? 0);
            $maxSeconds = (int) ($limit['max_seconds'] ?? 0);
            $maxScore = $limit['max_score'] ?? null;

            if ($elapsedSeconds >= $minSeconds && $elapsedSeconds < $maxSeconds && is_int($maxScore)) {
                return $maxScore;
            }
        }

        return null;
    }

    public function shouldAccumulateSuspicionPoints(): bool
    {
        return $this->antiCheatMode() === 'flag';
    }

    public function validateCollectedCoins(
        User $user,
        string $sessionToken,
        int $score,
        int $coinsCollected,
        array $metadata = [],
    ): void {
        $maxCoinsCollectedPerRun = (int) config('game.score_validation.max_coins_collected_per_run', 1000);
        $normalizedMetadata = $this->normalizeSubmissionMetadata($metadata);
        $duration = $normalizedMetadata['duration'] ?? null;

        if ($coinsCollected < 0 || $coinsCollected > $maxCoinsCollectedPerRun) {
            $this->securityEventLogger->logInvalidCollectedCoinsSubmission(
                $user,
                $sessionToken,
                $coinsCollected,
                'coins_out_of_range',
                [
                    'score' => $score,
                    'duration' => $duration,
                    'max_coins_collected_per_run' => $maxCoinsCollectedPerRun,
                ],
            );

            throw new BusinessException(
                'The submitted collected coin value is outside the allowed range.',
                errors: ['coins_collected' => ['The provided coins_collected value is invalid.']],
            );
        }
    }

    public function validateSubmissionMetadata(
        User $user,
        string $sessionToken,
        GameSession $gameSession,
        array $metadata,
    ): void {
        $normalizedMetadata = $this->normalizeSubmissionMetadata($metadata);

        if (array_key_exists('duration', $normalizedMetadata)) {
            $duration = $normalizedMetadata['duration'];
            $minDuration = (int) config('game.score_validation.min_duration_seconds', 5);
            $maxDuration = (int) config('game.score_validation.max_duration_seconds', 7200);

            if (! is_int($duration) || $duration < $minDuration || $duration > $maxDuration) {
                $this->securityEventLogger->logInvalidScoreSubmission(
                    $user,
                    $sessionToken,
                    null,
                    'invalid_duration_metadata',
                    [
                        'duration' => $duration,
                        'min_duration_seconds' => $minDuration,
                        'max_duration_seconds' => $maxDuration,
                    ],
                );

                throw new BusinessException(
                    'The submitted metadata is invalid.',
                    errors: ['metadata.duration' => ['The provided duration is invalid.']],
                );
            }
        }

        $sessionContext = $this->sessionContextMetadata($gameSession);

        if ($sessionContext === []) {
            return;
        }

        $mismatches = [];

        foreach ($sessionContext as $key => $expectedValue) {
            $receivedValue = $normalizedMetadata[$key] ?? null;

            if ($receivedValue !== $expectedValue) {
                $mismatches[$key] = [
                    'expected' => $expectedValue,
                    'received' => $receivedValue,
                ];
            }
        }

        if ($mismatches === []) {
            return;
        }

        $this->securityEventLogger->logSessionMetadataMismatch(
            $user,
            $sessionToken,
            $mismatches,
        );

        $errors = [];

        foreach (array_keys($mismatches) as $field) {
            $errors['metadata.'.$field] = [
                'The provided '.$field.' does not match the issued game session.',
            ];
        }

        throw new BusinessException(
            'The provided session metadata does not match the issued session.',
            errors: $errors,
        );
    }

    public function mergeSessionMetadata(GameSession $gameSession, array $metadata): ?array
    {
        $sessionMetadata = $this->sessionContextMetadata($gameSession);
        $normalizedMetadata = $this->normalizeSubmissionMetadata($metadata);

        if ($normalizedMetadata === []) {
            return $sessionMetadata === [] ? null : $sessionMetadata;
        }

        $sessionMetadata['submission'] = $normalizedMetadata;

        return $sessionMetadata;
    }

    public function markSessionSubmitted(GameSession $gameSession, array $metadata = []): void
    {
        $gameSession->forceFill([
            'status' => GameSessionStatus::SUBMITTED,
            'submitted_at' => now(),
            'metadata' => $this->mergeSessionMetadata($gameSession, $metadata),
        ])->save();
    }

    public function createScoreRecord(
        User $user,
        string $sessionToken,
        int $score,
        int $coinsCollected,
    ): GameScore {
        return GameScore::query()->create([
            'user_id' => $user->id,
            'score' => $score,
            'coins_collected' => $coinsCollected,
            'session_token' => $sessionToken,
            'is_processed' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeSubmissionMetadata(array $metadata): array
    {
        $normalized = [];

        if (array_key_exists('duration', $metadata)) {
            $normalized['duration'] = $metadata['duration'];
        }

        foreach (['device_id', 'platform', 'app_version'] as $key) {
            if (! array_key_exists($key, $metadata) || ! is_string($metadata[$key])) {
                continue;
            }

            $value = trim($metadata[$key]);

            if ($key === 'platform') {
                $value = mb_strtolower($value);
            }

            if ($value === '') {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    protected function sessionContextMetadata(GameSession $gameSession): array
    {
        $metadata = $gameSession->metadata;

        if (! is_array($metadata)) {
            return [];
        }

        unset($metadata['submission']);

        return $this->gameSessionService->normalizeSessionMetadata($metadata) ?? [];
    }

    protected function antiCheatMode(): string
    {
        $mode = Str::lower((string) config('game.anti_cheat.mode', config('game.score_validation.score_velocity_mode', 'flag')));

        if ($mode === 'reject') {
            return 'flag';
        }

        return in_array($mode, ['off', 'log', 'flag'], true) ? $mode : 'flag';
    }

    protected function isAntiCheatDetectionEnabled(): bool
    {
        return $this->antiCheatMode() !== 'off';
    }

    protected function softScoreVelocityThreshold(): float
    {
        return (float) config('game.anti_cheat.soft_score_velocity_threshold', config('game.score_validation.max_score_per_second', 4.0));
    }

    protected function softScoreMinimum(): int
    {
        return (int) config('game.anti_cheat.soft_score_minimum', 50);
    }
}
