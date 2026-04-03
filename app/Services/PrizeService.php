<?php

namespace App\Services;

use App\Enums\UserPrizeStatus;
use App\Exceptions\BusinessException;
use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use Illuminate\Database\Eloquent\Collection;

class PrizeService
{
    /**
     * @return Collection<int, UserPrize>
     */
    public function getUserPrizes(User $user): Collection
    {
        return $user->userPrizes()
            ->with('prize')
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();
    }

    public function findConfiguredPrizeByRank(int $rank): ?Prize
    {
        return Prize::query()
            ->whereNotNull('default_rank_from')
            ->whereNotNull('default_rank_to')
            ->where('default_rank_from', '<=', $rank)
            ->where('default_rank_to', '>=', $rank)
            ->orderBy('default_rank_from')
            ->orderBy('id')
            ->first();
    }

    public function findPrizeByRank(int $rank): ?Prize
    {
        $prize = $this->findConfiguredPrizeByRank($rank);

        if ($prize === null || ! $prize->is_active) {
            return null;
        }

        return $prize;
    }

    public function ensurePrizeCanBeAssigned(User $user, Prize $prize): void
    {
        if (! $prize->is_active) {
            throw new BusinessException(
                'This prize is not active.',
                errors: ['prize' => ['The selected prize is inactive.']],
            );
        }

        if (! $this->hasAvailableStock($prize)) {
            throw new BusinessException(
                'This prize is out of stock.',
                errors: ['prize' => ['The selected prize is not available.']],
            );
        }

        if ($this->hasDuplicateAssignment($user, $prize)) {
            throw new BusinessException(
                'This prize has already been assigned to the user.',
                errors: ['prize' => ['Duplicate prize assignment is not allowed.']],
            );
        }
    }

    public function hasAvailableStock(Prize $prize): bool
    {
        return $this->getAvailableStock($prize) > 0;
    }

    public function getAvailableStock(Prize $prize): int
    {
        if ((bool) config('game.prizes.use_remaining_stock', true)) {
            return max(0, $prize->quantity);
        }

        $assignedCount = UserPrize::query()
            ->where('prize_id', $prize->id)
            ->whereIn('status', [
                UserPrizeStatus::PENDING,
                UserPrizeStatus::ISSUED,
            ])
            ->count();

        return max(0, $prize->quantity - $assignedCount);
    }

    public function hasDuplicateAssignment(User $user, Prize $prize): bool
    {
        return UserPrize::query()
            ->where('user_id', $user->id)
            ->where('prize_id', $prize->id)
            ->whereIn('status', [
                UserPrizeStatus::PENDING,
                UserPrizeStatus::ISSUED,
            ])
            ->exists();
    }

    public function getAssignmentSkipReason(User $user, int $rank, ?array &$availableStockByPrize = null): ?string
    {
        $prize = $this->findConfiguredPrizeByRank($rank);

        if ($prize === null) {
            return 'no_matching_prize';
        }

        if (! $prize->is_active) {
            return 'prize_inactive';
        }

        if ($this->hasDuplicateAssignment($user, $prize)) {
            return 'duplicate_assignment';
        }

        $availableStock = is_array($availableStockByPrize)
            ? ($availableStockByPrize[$prize->id] ?? $this->getAvailableStock($prize))
            : $this->getAvailableStock($prize);

        if ($availableStock <= 0) {
            return 'out_of_stock';
        }

        if (is_array($availableStockByPrize)) {
            $availableStockByPrize[$prize->id] = $availableStock - 1;
        }

        return null;
    }
}
