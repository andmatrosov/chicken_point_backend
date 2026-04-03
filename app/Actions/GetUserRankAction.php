<?php

namespace App\Actions;

use App\Models\User;
use App\Services\LeaderboardService;

class GetUserRankAction
{
    public function __construct(
        protected LeaderboardService $leaderboardService,
    ) {
    }

    public function __invoke(User $user): int
    {
        return $this->leaderboardService->getCurrentUserRank($user);
    }
}
