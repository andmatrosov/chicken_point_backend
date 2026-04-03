<?php

namespace App\Actions;

use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use App\Services\AdminActionLogService;
use Illuminate\Support\Facades\DB;

class DeletePrizeAction
{
    public function __construct(
        protected AdminActionLogService $adminActionLogService,
    ) {
    }

    public function __invoke(Prize $prize, User $admin): void
    {
        $this->adminActionLogService->assertAdmin($admin);

        DB::transaction(function () use ($prize, $admin): void {
            $lockedPrize = Prize::query()
                ->whereKey($prize->id)
                ->lockForUpdate()
                ->firstOrFail();

            $assignments = UserPrize::query()
                ->where('prize_id', $lockedPrize->id)
                ->lockForUpdate()
                ->get(['id', 'user_id', 'status']);

            $affectedUserIds = $assignments
                ->pluck('user_id')
                ->unique()
                ->values()
                ->all();

            UserPrize::query()
                ->where('prize_id', $lockedPrize->id)
                ->delete();

            $lockedPrize->delete();

            $this->adminActionLogService->log(
                $admin,
                'delete_prize',
                'prize',
                $prize->id,
                [
                    'prize_id' => $prize->id,
                    'prize_title' => $prize->title,
                    'number_of_deleted_assignments' => $assignments->count(),
                    'affected_user_ids' => $affectedUserIds,
                    'deleted_assignment_ids' => $assignments->pluck('id')->values()->all(),
                    'remaining_stock_model' => (bool) config('game.prizes.use_remaining_stock', true),
                    'quantity_before_delete' => $prize->quantity,
                ],
            );
        });
    }
}
