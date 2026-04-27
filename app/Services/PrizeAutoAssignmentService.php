<?php

namespace App\Services;

use App\Actions\AssignPrizeByRankAction;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use JsonException;

class PrizeAutoAssignmentService
{
    public function __construct(
        protected LeaderboardService $leaderboardService,
        protected PrizeService $prizeService,
        protected AssignPrizeByRankAction $assignPrizeByRankAction,
        protected AdminActionLogService $adminActionLogService,
        protected SecurityEventLogger $securityEventLogger,
        protected FrozenLeaderboardService $frozenLeaderboardService,
    ) {}

    /**
     * @return array{
     *     mode: string,
     *     snapshot: array<string, mixed>,
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

        $snapshot = $this->buildCurrentLeaderboardSnapshot();
        $availableStockByPrize = [];
        $entries = [];

        foreach ($this->getUsersFromSnapshot($snapshot) as $user) {
            $entries[] = $this->buildPreviewEntry($user, $availableStockByPrize);
        }

        return $this->buildResult('preview', $entries, $snapshot);
    }

    /**
     * @return array{
     *     mode: string,
     *     snapshot: array<string, mixed>,
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

        return $this->assignLeaderboardSnapshotPrizes(
            $admin,
            $this->buildCurrentLeaderboardSnapshot(),
        );
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{
     *     mode: string,
     *     snapshot: array<string, mixed>,
     *     processed_count: int,
     *     ready_count: int,
     *     assigned_count: int,
     *     skipped_count: int,
     *     entries: array<int, array<string, mixed>>
     * }
     */
    public function assignPreviewedLeaderboardPrizes(User $admin, array $snapshot): array
    {
        Gate::forUser($admin)->authorize('auto-assign-prizes');

        return $this->assignLeaderboardSnapshotPrizes($admin, $snapshot);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{
     *     mode: string,
     *     snapshot: array<string, mixed>,
     *     processed_count: int,
     *     ready_count: int,
     *     assigned_count: int,
     *     skipped_count: int,
     *     entries: array<int, array<string, mixed>>
     * }
     */
    protected function assignLeaderboardSnapshotPrizes(User $admin, array $snapshot): array
    {
        $validatedSnapshot = $this->validateSnapshot($snapshot);

        return DB::transaction(function () use ($admin, $validatedSnapshot): array {
            $entries = [];

            foreach ($this->getUsersFromSnapshot($validatedSnapshot) as $user) {
                $entries[] = $this->assignEntry($user, $admin);
            }

            $result = $this->buildResult('assign', $entries, $validatedSnapshot);
            $this->frozenLeaderboardService->freezeFromPrizeSnapshot($validatedSnapshot, $admin);

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
     *     snapshot: array<string, mixed>,
     *     processed_count: int,
     *     ready_count: int,
     *     assigned_count: int,
     *     skipped_count: int,
     *     entries: array<int, array<string, mixed>>
     * }
     */
    protected function buildResult(string $mode, array $entries, array $snapshot): array
    {
        $entryCollection = Collection::make($entries);

        return [
            'mode' => $mode,
            'snapshot' => $snapshot,
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
            'no_matching_prize' => 'Для этого ранга не настроен приз.',
            'prize_inactive' => 'Настроенный приз сейчас неактивен.',
            'duplicate_assignment' => 'У пользователя уже есть активное назначение этого приза.',
            'out_of_stock' => 'Для выбранного приза недостаточно остатка.',
            default => 'Назначение приза выполнить нельзя.',
        };
    }

    /**
     * @return array{
     *     captured_at: string,
     *     hash: string,
     *     entries: array<int, array{user_id: int, rank: int, best_score: int}>
     * }
     */
    protected function buildCurrentLeaderboardSnapshot(): array
    {
        $entries = $this->leaderboardService->getTopEntries()
            ->map(fn (User $user): array => [
                'user_id' => $user->id,
                'rank' => (int) $user->getAttribute('rank'),
                'best_score' => (int) $user->best_score,
            ])
            ->values()
            ->all();

        $leaderboardEntries = $this->leaderboardService->buildFrozenSnapshotEntries();
        $leaderboardComparableEntries = collect($leaderboardEntries)
            ->map(fn (array $entry): array => [
                'user_id' => $entry['user_id'],
                'rank' => $entry['rank'],
                'best_score' => $entry['best_score'],
            ])
            ->all();

        return [
            'captured_at' => now()->toIso8601String(),
            'hash' => $this->hashSnapshotEntries($entries),
            'leaderboard_hash' => $this->hashSnapshotEntries($leaderboardComparableEntries),
            'entries' => $entries,
            'leaderboard_entries' => $leaderboardEntries,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{
     *     captured_at: string,
     *     hash: string,
     *     entries: array<int, array{user_id: int, rank: int, best_score: int}>
     * }
     */
    protected function validateSnapshot(array $snapshot): array
    {
        $entries = $snapshot['entries'] ?? null;
        $capturedAt = $snapshot['captured_at'] ?? null;
        $hash = $snapshot['hash'] ?? null;
        $leaderboardEntries = $snapshot['leaderboard_entries'] ?? null;
        $leaderboardHash = $snapshot['leaderboard_hash'] ?? null;

        if (! is_array($entries) || ! is_string($capturedAt) || ! is_string($hash)) {
            throw $this->invalidSnapshotException();
        }

        $normalizedEntries = [];
        $seenUserIds = [];
        $seenRanks = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                throw $this->invalidSnapshotException();
            }

            $userId = $entry['user_id'] ?? null;
            $rank = $entry['rank'] ?? null;
            $bestScore = $entry['best_score'] ?? null;

            if (! is_int($userId) || ! is_int($rank) || ! is_int($bestScore) || $rank < 1) {
                throw $this->invalidSnapshotException();
            }

            if (isset($seenUserIds[$userId]) || isset($seenRanks[$rank])) {
                throw $this->invalidSnapshotException();
            }

            $seenUserIds[$userId] = true;
            $seenRanks[$rank] = true;

            $normalizedEntries[] = [
                'user_id' => $userId,
                'rank' => $rank,
                'best_score' => $bestScore,
            ];
        }

        if ($hash !== $this->hashSnapshotEntries($normalizedEntries)) {
            throw $this->invalidSnapshotException();
        }

        $validatedSnapshot = [
            'captured_at' => $capturedAt,
            'hash' => $hash,
            'entries' => $normalizedEntries,
        ];

        if (is_array($leaderboardEntries)) {
            $normalizedLeaderboardEntries = [];

            foreach ($leaderboardEntries as $entry) {
                if (! is_array($entry)) {
                    throw $this->invalidSnapshotException();
                }

                $userId = $entry['user_id'] ?? null;
                $rank = $entry['rank'] ?? null;
                $bestScore = $entry['best_score'] ?? null;
                $email = $entry['email'] ?? null;
                $maskedEmail = $entry['masked_email'] ?? null;

                if (! is_int($userId) || ! is_int($rank) || ! is_int($bestScore) || ! is_string($email) || ! is_string($maskedEmail) || $rank < 1) {
                    throw $this->invalidSnapshotException();
                }

                $normalizedLeaderboardEntries[] = [
                    'user_id' => $userId,
                    'rank' => $rank,
                    'best_score' => $bestScore,
                    'email' => $email,
                    'masked_email' => $maskedEmail,
                ];
            }

            if (is_string($leaderboardHash)) {
                $comparableEntries = collect($normalizedLeaderboardEntries)
                    ->map(fn (array $entry): array => [
                        'user_id' => $entry['user_id'],
                        'rank' => $entry['rank'],
                        'best_score' => $entry['best_score'],
                    ])
                    ->all();

                if ($leaderboardHash !== $this->hashSnapshotEntries($comparableEntries)) {
                    throw $this->invalidSnapshotException();
                }
            }

            $validatedSnapshot['leaderboard_hash'] = is_string($leaderboardHash) ? $leaderboardHash : null;
            $validatedSnapshot['leaderboard_entries'] = $normalizedLeaderboardEntries;
        }

        return $validatedSnapshot;
    }

    /**
     * @param  array{
     *     captured_at: string,
     *     hash: string,
     *     entries: array<int, array{user_id: int, rank: int, best_score: int}>
     * }  $snapshot
     * @return Collection<int, User>
     */
    protected function getUsersFromSnapshot(array $snapshot): Collection
    {
        $snapshotEntries = Collection::make($snapshot['entries']);

        if ($snapshotEntries->isEmpty()) {
            return collect();
        }

        $usersById = User::query()
            ->whereIn('id', $snapshotEntries->pluck('user_id')->all())
            ->get()
            ->keyBy('id');

        if ($usersById->count() !== $snapshotEntries->count()) {
            throw new BusinessException(
                'Сохраненный предпросмотр больше неактуален. Сформируйте новый перед подтверждением назначений.',
                errors: ['preview' => ['Снимок таблицы лидеров содержит пользователей, которые больше недоступны.']],
            );
        }

        return $snapshotEntries
            ->map(function (array $snapshotEntry) use ($usersById): User {
                /** @var User|null $user */
                $user = $usersById->get($snapshotEntry['user_id']);

                if ($user === null) {
                    throw $this->invalidSnapshotException();
                }

                $user->setAttribute('rank', $snapshotEntry['rank']);
                $user->setAttribute('snapshot_best_score', $snapshotEntry['best_score']);

                return $user;
            })
            ->values();
    }

    /**
     * @param  array<int, array{user_id: int, rank: int, best_score: int}>  $entries
     */
    protected function hashSnapshotEntries(array $entries): string
    {
        try {
            return hash('sha256', json_encode($entries, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            throw $this->invalidSnapshotException();
        }
    }

    protected function invalidSnapshotException(): BusinessException
    {
        return new BusinessException(
            'Перед подтверждением назначений сформируйте новый предпросмотр.',
            errors: ['preview' => ['Сохраненный предпросмотр отсутствует или поврежден.']],
        );
    }

    /**
     * @param  array{
     *     mode: string,
     *     snapshot: array<string, mixed>,
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
