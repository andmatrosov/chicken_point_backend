<?php

namespace App\Actions;

use App\Enums\GameSessionStatus;
use App\Models\GameSession;
use App\Models\User;
use App\Services\ScoreSubmissionService;
use Illuminate\Support\Facades\DB;

class SubmitScoreAction
{
    public function __construct(
        protected ScoreSubmissionService $scoreSubmissionService,
    ) {
    }

    public function __invoke(
        User $user,
        string $sessionToken,
        int $score,
        array $metadata = [],
    ): User {
        return DB::transaction(function () use ($user, $sessionToken, $score, $metadata): User {
            $gameSession = GameSession::query()
                ->where('token', $sessionToken)
                ->lockForUpdate()
                ->first();

            $gameSession = $this->scoreSubmissionService->validateSessionOwnershipAndState(
                $user,
                $sessionToken,
                $gameSession,
            );

            $this->scoreSubmissionService->validateScore($user, $sessionToken, $score);
            $this->scoreSubmissionService->validateMetadata($user, $sessionToken, $metadata);

            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->scoreSubmissionService->createScoreRecord($lockedUser, $sessionToken, $score);

            $gameSession->forceFill([
                'status' => GameSessionStatus::SUBMITTED,
                'submitted_at' => now(),
                'metadata' => $this->scoreSubmissionService->mergeSessionMetadata($gameSession, $metadata),
            ])->save();

            if ($score > $lockedUser->best_score) {
                $lockedUser->best_score = $score;
            }

            $lockedUser->save();

            return $lockedUser->fresh()->load('activeSkin')->loadCount('skins');
        });
    }
}
