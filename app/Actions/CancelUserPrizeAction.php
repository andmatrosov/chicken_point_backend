<?php

namespace App\Actions;

use App\Enums\UserPrizeStatus;
use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use App\Services\AdminActionLogService;
use Illuminate\Support\Facades\DB;

class CancelUserPrizeAction
{
    public function __construct(
        protected AdminActionLogService $adminActionLogService,
    ) {
    }

    public function __invoke(UserPrize $userPrize, User $admin): UserPrize
    {
        $this->adminActionLogService->assertAdmin($admin);

        return DB::transaction(function () use ($userPrize, $admin): UserPrize {
            $lockedUserPrize = UserPrize::query()
                ->with(['user', 'prize', 'assignedBy'])
                ->whereKey($userPrize->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPrize = Prize::query()
                ->whereKey($lockedUserPrize->prize_id)
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = $lockedUserPrize->status;
            $newStatus = UserPrizeStatus::CANCELED;
            $stockDelta = 0;

            if ($previousStatus !== UserPrizeStatus::CANCELED) {
                $lockedUserPrize->forceFill([
                    'status' => $newStatus,
                ])->save();

                if ((bool) config('game.prizes.use_remaining_stock', true)) {
                    $lockedPrize->increment('quantity');
                    $stockDelta = 1;
                }
            }

            $this->adminActionLogService->log(
                $admin,
                'cancel_prize_assignment',
                'prize_assignment',
                $lockedUserPrize->id,
                [
                    'user_prize_id' => $lockedUserPrize->id,
                    'prize_id' => $lockedPrize->id,
                    'user_id' => $lockedUserPrize->user_id,
                    'previous_status' => $previousStatus->value,
                    'new_status' => $newStatus->value,
                    'stock_delta' => $stockDelta,
                ],
            );

            return $lockedUserPrize->fresh(['user', 'prize', 'assignedBy']);
        });
    }
}
