<?php

namespace App\Actions;

use App\Enums\UserPrizeStatus;
use App\Exceptions\BusinessException;
use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use App\Services\AdminActionLogService;
use App\Services\PrizeService;
use Illuminate\Support\Facades\DB;

class AssignPrizeByRankAction
{
    public function __construct(
        protected PrizeService $prizeService,
        protected AdminActionLogService $adminActionLogService,
    ) {
    }

    public function __invoke(
        User $user,
        int $rank,
        User $admin,
    ): UserPrize {
        $this->adminActionLogService->assertAdmin($admin);

        $prize = $this->prizeService->findPrizeByRank($rank);

        if ($prize === null) {
            throw new BusinessException(
                'No active prize is configured for the specified rank.',
                errors: ['rank' => ['No prize matches the provided rank.']],
            );
        }

        return DB::transaction(function () use ($user, $rank, $admin, $prize): UserPrize {
            $lockedPrize = Prize::query()
                ->whereKey($prize->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->prizeService->ensurePrizeCanBeAssigned($user, $lockedPrize);

            $userPrize = UserPrize::query()->create([
                'user_id' => $user->id,
                'prize_id' => $lockedPrize->id,
                'rank_at_assignment' => $rank,
                'assigned_manually' => false,
                'assigned_by' => $admin->id,
                'assigned_at' => now(),
                'status' => UserPrizeStatus::PENDING,
            ]);

            if ((bool) config('game.prizes.use_remaining_stock', true)) {
                $lockedPrize->decrement('quantity');
            }

            $this->adminActionLogService->log(
                $admin,
                'assign_prize_by_rank',
                'prize_assignment',
                $userPrize->id,
                [
                    'user_id' => $user->id,
                    'prize_id' => $lockedPrize->id,
                    'rank_at_assignment' => $rank,
                    'assigned_count' => 1,
                    'assigned_manually' => false,
                ],
            );

            return $userPrize->load('prize');
        });
    }
}
