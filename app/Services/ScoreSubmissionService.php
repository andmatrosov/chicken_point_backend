<?php

namespace App\Services;

use App\Enums\GameSessionStatus;
use App\Exceptions\BusinessException;
use App\Models\GameScore;
use App\Models\GameSession;
use App\Models\User;

class ScoreSubmissionService
{
    public function __construct(
        protected GameSessionService $gameSessionService,
        protected SecurityEventLogger $securityEventLogger,
    ) {
    }

    public function validateSessionOwnershipAndState(
        User $user,
        string $sessionToken,
        ?GameSession $gameSession,
    ): GameSession {
        if ($gameSession === null) {
            $this->logRejectedSubmission($user, $sessionToken, null, 'missing_session');

            throw new BusinessException(
                'Invalid game session.',
                errors: ['session_token' => ['The provided session token is invalid.']],
            );
        }

        if ($gameSession->user_id !== $user->id) {
            $this->logRejectedSubmission($user, $sessionToken, null, 'foreign_session');

            throw new BusinessException(
                'This session does not belong to the current user.',
                403,
                ['session_token' => ['The provided session token belongs to another user.']],
            );
        }

        if ($gameSession->status === GameSessionStatus::SUBMITTED) {
            $this->logRejectedSubmission($user, $sessionToken, null, 'duplicate_submission');

            throw new BusinessException(
                'This session has already been submitted.',
                errors: ['session_token' => ['The provided session token has already been used.']],
            );
        }

        if ($gameSession->status !== GameSessionStatus::ACTIVE) {
            $this->logRejectedSubmission($user, $sessionToken, null, 'inactive_session');

            throw new BusinessException(
                'This session is not available for score submission.',
                errors: ['session_token' => ['The provided session token is not active.']],
            );
        }

        if ($this->gameSessionService->isExpired($gameSession)) {
            $gameSession->forceFill([
                'status' => GameSessionStatus::EXPIRED,
            ])->save();

            $this->logRejectedSubmission($user, $sessionToken, null, 'expired_session');

            throw new BusinessException(
                'This session has expired.',
                errors: ['session_token' => ['The provided session token has expired.']],
            );
        }

        return $gameSession;
    }

    public function validateScore(User $user, string $sessionToken, int $score): void
    {
        $minScore = (int) config('game.score_validation.min_score', 0);
        $maxScore = (int) config('game.score_validation.max_score', 1000000);

        if ($score < $minScore || $score > $maxScore) {
            $this->logRejectedSubmission($user, $sessionToken, $score, 'score_out_of_range');

            throw new BusinessException(
                'The submitted score is outside the allowed range.',
                errors: ['score' => ['The provided score is invalid.']],
            );
        }
    }

    public function validateMetadata(User $user, string $sessionToken, array $metadata): void
    {
        if ($metadata === []) {
            return;
        }

        if (array_key_exists('duration', $metadata)) {
            $duration = $metadata['duration'];
            $minDuration = (int) config('game.score_validation.min_duration_seconds', 5);
            $maxDuration = (int) config('game.score_validation.max_duration_seconds', 7200);

            if (! is_int($duration) || $duration < $minDuration || $duration > $maxDuration) {
                $this->logRejectedSubmission($user, $sessionToken, null, 'invalid_duration_metadata', [
                    'metadata_keys' => array_keys($metadata),
                    'duration' => $metadata['duration'] ?? null,
                ]);

                throw new BusinessException(
                    'The submitted metadata is invalid.',
                    errors: ['metadata.duration' => ['The provided duration is invalid.']],
                );
            }
        }

    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logRejectedSubmission(
        User $user,
        string $sessionToken,
        ?int $score,
        string $reason,
        array $context = [],
    ): void {
        $this->securityEventLogger->logScoreSubmissionRejection(
            $user,
            $sessionToken,
            $score,
            $reason,
            $context,
        );
    }

    public function mergeSessionMetadata(GameSession $gameSession, array $metadata): ?array
    {
        $sessionMetadata = $gameSession->metadata ?? [];

        if ($metadata === []) {
            return $sessionMetadata === [] ? null : $sessionMetadata;
        }

        $sessionMetadata['submission'] = $metadata;

        return $sessionMetadata;
    }

    public function createScoreRecord(User $user, string $sessionToken, int $score): GameScore
    {
        return GameScore::query()->create([
            'user_id' => $user->id,
            'score' => $score,
            'session_token' => $sessionToken,
            'is_processed' => true,
        ]);
    }
}
