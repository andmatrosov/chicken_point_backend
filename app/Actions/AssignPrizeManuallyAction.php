<?php

namespace App\Actions;

use App\Enums\UserPrizeStatus;
use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use App\Services\AdminActionLogService;
use App\Services\PrizeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AssignPrizeManuallyAction
{
    public function __construct(
        protected PrizeService $prizeService,
        protected AdminActionLogService $adminActionLogService,
    ) {
    }

    public function __invoke(
        User $user,
        Prize $prize,
        User $admin,
        ?int $rankAtAssignment = null,
    ): UserPrize {
        Gate::forUser($admin)->authorize('assign-prize-manually');

        return DB::transaction(function () use ($user, $prize, $admin, $rankAtAssignment): UserPrize {
            $lockedPrize = Prize::query()
                ->whereKey($prize->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->prizeService->ensurePrizeCanBeAssigned($user, $lockedPrize);

            $userPrize = UserPrize::query()->create([
                'user_id' => $user->id,
                'prize_id' => $lockedPrize->id,
                'rank_at_assignment' => $rankAtAssignment,
                'assigned_manually' => true,
                'assigned_by' => $admin->id,
                'assigned_at' => now(),
                'status' => UserPrizeStatus::PENDING,
            ]);

            if ((bool) config('game.prizes.use_remaining_stock', true)) {
                $lockedPrize->decrement('quantity');
            }

            $this->adminActionLogService->log(
                $admin,
                'assign_prize_manually',
                'prize_assignment',
                $userPrize->id,
                [
                    'user_id' => $user->id,
                    'prize_id' => $lockedPrize->id,
                    'rank_at_assignment' => $rankAtAssignment,
                    'assigned_count' => 1,
                    'assigned_manually' => true,
                ],
            );

            return $userPrize->load('prize');
        });
    }
}
