<?php

namespace App\Services;

use App\Models\User;

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
