<?php

namespace App\Services;

use App\Data\Game\ScoreSuspicionResult;
use App\Models\GameScore;
use App\Models\User;
use App\Models\UserSuspiciousEvent;

class SuspiciousGameResultFlagService
{
    public const REASON_MANUAL_ADMIN_FLAG = 'manual_admin_flag';

    /**
     * @param  array<string, mixed>  $context
     */
    public function addSuspicionPoints(User $user, int $points, string $reason, array $context = []): void
    {
        if ($points <= 0) {
            return;
        }

        $newTotalPoints = (int) $user->suspicious_game_result_points + $points;

        $user->forceFill([
            'suspicious_game_result_points' => $newTotalPoints,
        ])->save();

        if ($newTotalPoints >= $this->flagThreshold() && ! $user->has_suspicious_game_results) {
            $this->flag($user, $reason);
        }
    }

    public function recordSuspiciousEvent(User $user, GameScore $gameScore, ScoreSuspicionResult $result): UserSuspiciousEvent
    {
        return UserSuspiciousEvent::query()->firstOrCreate(
            ['game_score_id' => $gameScore->id],
            [
                'user_id' => $user->id,
                'reason' => $result->reason,
                'points' => $result->points,
                'signals' => $result->signals,
                'context' => $result->context,
            ],
        );
    }

    public function flag(User $user, string $reason): void
    {
        if ($user->has_suspicious_game_results && $user->suspicious_game_results_reason !== null) {
            return;
        }

        $payload = [
            'has_suspicious_game_results' => true,
            'suspicious_game_results_reason' => $reason,
        ];

        if ($user->suspicious_game_results_flagged_at === null) {
            $payload['suspicious_game_results_flagged_at'] = now();
        }

        $user->forceFill($payload)->save();
    }

    public function clearFlag(User $user): void
    {
        if (! $user->has_suspicious_game_results
            && $user->suspicious_game_results_flagged_at === null
            && $user->suspicious_game_results_reason === null) {
            return;
        }

        $user->forceFill([
            'has_suspicious_game_results' => false,
            'suspicious_game_results_flagged_at' => null,
            'suspicious_game_results_reason' => null,
        ])->save();
    }

    public function resetPoints(User $user): void
    {
        if ((int) $user->suspicious_game_result_points === 0) {
            return;
        }

        $user->forceFill([
            'suspicious_game_result_points' => 0,
        ])->save();
    }

    public function setPoints(User $user, int $points): void
    {
        $points = max(0, $points);

        if ((int) $user->suspicious_game_result_points === $points) {
            return;
        }

        // Manual points correction must not implicitly change the persistent flag.
        $user->forceFill([
            'suspicious_game_result_points' => $points,
        ])->save();
    }

    public function syncManualFlag(User $user, bool $shouldBeFlagged): void
    {
        if ($shouldBeFlagged) {
            $this->flag($user, self::REASON_MANUAL_ADMIN_FLAG);

            return;
        }

        $this->clearFlag($user);
    }

    protected function flagThreshold(): int
    {
        return max(1, (int) config('game.anti_cheat.suspicion_points_to_flag', 3));
    }
}
