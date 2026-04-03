<?php

namespace App\Services;

use App\Actions\AssignPrizeByRankAction;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class PrizeAutoAssignmentService
{
    public function __construct(
        protected LeaderboardService $leaderboardService,
        protected PrizeService $prizeService,
        protected AssignPrizeByRankAction $assignPrizeByRankAction,
        protected AdminActionLogService $adminActionLogService,
        protected SecurityEventLogger $securityEventLogger,
    ) {
    }

    /**
     * @return array{
     *     mode: string,
     *     processed_count: int,
     *     ready_count: int,
     *     assigned_count: int,
     *     skipped_count: int,
     *     entries: array<int, array<string, mixed>>
     * }
     */
    public function previewCurrentLeaderboardAssignments(User $admin): array
    {
        Gate::forUser($admin)->authorize('auto-assign-prizes');

        $availableStockByPrize = [];
        $entries = [];

        foreach ($this->leaderboardService->getTopEntries() as $user) {
            $entries[] = $this->buildPreviewEntry($user, $availableStockByPrize);
        }

        return $this->buildResult('preview', $entries);
    }

    /**
     * @return array{
     *     mode: string,
     *     processed_count: int,
     *     ready_count: int,
     *     assigned_count: int,
     *     skipped_count: int,
     *     entries: array<int, array<string, mixed>>
     * }
     */
    public function assignCurrentLeaderboardPrizes(User $admin): array
    {
        Gate::forUser($admin)->authorize('auto-assign-prizes');

        return DB::transaction(function () use ($admin): array {
            $entries = [];

            foreach ($this->leaderboardService->getTopEntries() as $user) {
                $entries[] = $this->assignEntry($user, $admin);
            }

            $result = $this->buildResult('assign', $entries);

            $this->logAutoAssignmentRun($admin, $result);

            return $result;
        });
    }

    /**
     * @param  array<int, int>  $availableStockByPrize
     * @return array<string, mixed>
     */
    protected function buildPreviewEntry(User $user, array &$availableStockByPrize): array
    {
        $rank = (int) $user->getAttribute('rank');
        $prize = $this->prizeService->findConfiguredPrizeByRank($rank);
        $reason = $this->prizeService->getAssignmentSkipReason($user, $rank, $availableStockByPrize);

        if ($reason !== null) {
            return $this->makeEntry(
                user: $user,
                rank: $rank,
                prizeId: $prize?->id,
                status: 'warning',
                reason: $reason,
                warning: $this->mapWarningMessage($reason),
                prizeTitle: $prize?->title,
            );
        }

        return $this->makeEntry(
            user: $user,
            rank: $rank,
            prizeId: $prize?->id,
            status: 'ready',
            prizeTitle: $prize?->title,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function assignEntry(User $user, User $admin): array
    {
        $rank = (int) $user->getAttribute('rank');
        $prize = $this->prizeService->findConfiguredPrizeByRank($rank);
        $reason = $this->prizeService->getAssignmentSkipReason($user, $rank);

        if ($reason !== null) {
            return $this->makeEntry(
                user: $user,
                rank: $rank,
                prizeId: $prize?->id,
                status: 'skipped',
                reason: $reason,
                warning: $this->mapWarningMessage($reason),
                prizeTitle: $prize?->title,
            );
        }

        try {
            $userPrize = ($this->assignPrizeByRankAction)($user, $rank, $admin);
        } catch (BusinessException $exception) {
            $this->securityEventLogger->logBusinessFailure('auto_prize_assignment_failed', [
                'user_id' => $user->id,
                'rank' => $rank,
                'prize_id' => $prize?->id,
                'error_message' => $exception->getMessage(),
            ]);

            return $this->makeEntry(
                user: $user,
                rank: $rank,
                prizeId: $prize?->id,
                status: 'skipped',
                reason: 'assignment_failed',
                warning: $exception->getMessage(),
                prizeTitle: $prize?->title,
            );
        }

        return $this->makeEntry(
            user: $user,
            rank: $rank,
            prizeId: $userPrize->prize_id,
            status: 'assigned',
            prizeTitle: $userPrize->prize?->title,
            userPrizeId: $userPrize->id,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array{
     *     mode: string,
     *     processed_count: int,
     *     ready_count: int,
     *     assigned_count: int,
     *     skipped_count: int,
     *     entries: array<int, array<string, mixed>>
     * }
     */
    protected function buildResult(string $mode, array $entries): array
    {
        $entryCollection = Collection::make($entries);

        return [
            'mode' => $mode,
            'processed_count' => $entryCollection->count(),
            'ready_count' => $entryCollection->where('status', 'ready')->count(),
            'assigned_count' => $entryCollection->where('status', 'assigned')->count(),
            'skipped_count' => $entryCollection->filter(
                fn (array $entry): bool => in_array($entry['status'], ['skipped', 'warning'], true),
            )->count(),
            'entries' => $entries,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeEntry(
        User $user,
        int $rank,
        ?int $prizeId,
        string $status,
        ?string $reason = null,
        ?string $warning = null,
        ?string $prizeTitle = null,
        ?int $userPrizeId = null,
    ): array {
        return [
            'user_id' => $user->id,
            'rank' => $rank,
            'prize_id' => $prizeId,
            'prize_title' => $prizeTitle,
            'status' => $status,
            'reason' => $reason,
            'warning' => $warning,
            'user_prize_id' => $userPrizeId,
        ];
    }

    protected function mapWarningMessage(string $reason): string
    {
        return match ($reason) {
            'no_matching_prize' => 'No prize is configured for this rank.',
            'prize_inactive' => 'The configured prize is inactive.',
            'duplicate_assignment' => 'An active prize assignment already exists for this user and prize.',
            'out_of_stock' => 'The configured prize does not have enough stock.',
            default => 'Prize assignment cannot be completed.',
        };
    }

    /**
     * @param  array{
     *     mode: string,
     *     processed_count: int,
     *     ready_count: int,
     *     assigned_count: int,
     *     skipped_count: int,
     *     entries: array<int, array<string, mixed>>
     * }  $result
     */
    protected function logAutoAssignmentRun(User $adminUser, array $result): void
    {
        $this->adminActionLogService->log(
            $adminUser,
            'auto_assign_prizes',
            'prize_assignment',
            0,
            [
                'processed_count' => $result['processed_count'],
                'assigned_count' => $result['assigned_count'],
                'skipped_count' => $result['skipped_count'],
                'entries' => $result['entries'],
            ],
        );
    }
}
