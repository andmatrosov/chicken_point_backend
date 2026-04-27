<?php

namespace App\Actions;

use App\Models\User;
use App\Services\ScoreSubmissionService;
use App\Services\SecurityEventLogger;
use App\Services\SuspiciousGameResultFlagService;
use Illuminate\Support\Facades\DB;

class SubmitScoreAction
{
    public function __construct(
        protected ScoreSubmissionService $scoreSubmissionService,
        protected SuspiciousGameResultFlagService $suspiciousGameResultFlagService,
        protected SecurityEventLogger $securityEventLogger,
    ) {}

    public function __invoke(
        User $user,
        string $sessionToken,
        int $score,
        int $coinsCollected,
        array $metadata = [],
    ): User {
        return DB::transaction(function () use ($user, $sessionToken, $score, $coinsCollected, $metadata): User {
            $gameSession = $this->scoreSubmissionService->lockSessionForSubmission($user, $sessionToken);

            $this->scoreSubmissionService->validateScore($user, $sessionToken, $score);
            $suspicionResult = $this->scoreSubmissionService->detectSuspiciousScoreSubmission($gameSession, $score);
            $this->scoreSubmissionService->validateCollectedCoins(
                $user,
                $sessionToken,
                $score,
                $coinsCollected,
                $metadata,
            );
            $this->scoreSubmissionService->validateSubmissionMetadata(
                $user,
                $sessionToken,
                $gameSession,
                $metadata,
            );

            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->scoreSubmissionService->createScoreRecord(
                $lockedUser,
                $sessionToken,
                $score,
                $coinsCollected,
            );

            $this->scoreSubmissionService->markSessionSubmitted($gameSession, $metadata);

            if ($score > $lockedUser->best_score) {
                $lockedUser->best_score = $score;
            }

            $lockedUser->coins += $coinsCollected;
            $lockedUser->save();

            if ($suspicionResult->isSuspicious && $this->scoreSubmissionService->shouldAccumulateSuspicionPoints()) {
                $this->suspiciousGameResultFlagService->addSuspicionPoints(
                    $lockedUser,
                    $suspicionResult->points,
                    $suspicionResult->reason,
                    $suspicionResult->context,
                );
            }

            if ($suspicionResult->isSuspicious) {
                $lockedUser->refresh();

                $this->securityEventLogger->logSuspiciousScoreSubmission(
                    $lockedUser,
                    (int) $gameSession->id,
                    $score,
                    array_merge($suspicionResult->context, [
                        'points_added' => $this->scoreSubmissionService->shouldAccumulateSuspicionPoints()
                            ? $suspicionResult->points
                            : 0,
                        'total_points_after' => (int) $lockedUser->suspicious_game_result_points,
                        'reason' => $suspicionResult->reason,
                    ]),
                );
            }

            return $lockedUser->fresh()->load('activeSkin')->loadCount('skins');
        });
    }
}
