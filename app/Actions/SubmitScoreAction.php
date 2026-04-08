<?php

namespace App\Actions;

use App\Models\User;
use App\Services\ScoreSubmissionService;
use Illuminate\Support\Facades\DB;

class SubmitScoreAction
{
    public function __construct(
        protected ScoreSubmissionService $scoreSubmissionService,
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

            return $lockedUser->fresh()->load('activeSkin')->loadCount('skins');
        });
    }
}
