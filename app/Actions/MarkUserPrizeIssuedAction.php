<?php

namespace App\Actions;

use App\Enums\UserPrizeStatus;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Models\UserPrize;
use App\Services\AdminActionLogService;
use Illuminate\Support\Facades\DB;

class MarkUserPrizeIssuedAction
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

            $previousStatus = $lockedUserPrize->status;
            $newStatus = UserPrizeStatus::ISSUED;

            if ($previousStatus !== UserPrizeStatus::PENDING) {
                throw new BusinessException(
                    'Only pending prize assignments can be marked as issued.',
                    errors: ['status' => ['The selected prize assignment is not pending.']],
                );
            }

            $lockedUserPrize->forceFill([
                'status' => $newStatus,
            ])->save();

            $this->adminActionLogService->log(
                $admin,
                'mark_prize_assignment_issued',
                'prize_assignment',
                $lockedUserPrize->id,
                [
                    'user_prize_id' => $lockedUserPrize->id,
                    'prize_id' => $lockedUserPrize->prize_id,
                    'user_id' => $lockedUserPrize->user_id,
                    'previous_status' => $previousStatus->value,
                    'new_status' => $newStatus->value,
                    'stock_delta' => 0,
                ],
            );

            return $lockedUserPrize->fresh(['user', 'prize', 'assignedBy']);
        });
    }
}
