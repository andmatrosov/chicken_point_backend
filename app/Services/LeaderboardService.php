<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LeaderboardService
{
    public function __construct(
        protected FrozenLeaderboardService $frozenLeaderboardService,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function getTopEntries(?int $limit = null): Collection
    {
        $size = $limit ?? (int) config('game.leaderboard.size', 15);

        $frozenSnapshot = $this->frozenLeaderboardService->getActiveSnapshot();

        if ($frozenSnapshot !== null) {
            return $this->mapSnapshotEntriesToUsers(
                (array) data_get($frozenSnapshot->payload, 'entries', []),
                $size,
            );
        }

        return $this->leaderboardEligibleUsers()
            ->select(['id', 'email', 'best_score'])
            ->orderByDesc('best_score')
            ->orderBy('id')
            ->limit($size)
            ->get()
            ->values()
            ->map(function (User $user, int $index): User {
                $user->setAttribute('rank', $index + 1);
                $user->setAttribute('masked_email', $this->maskEmail($user->email));

                return $user;
            });
    }

    public function getCurrentUserRank(User $user): ?int
    {
        $frozenEntry = $this->getFrozenSnapshotEntryForUser($user);

        if ($frozenEntry !== null) {
            return (int) $frozenEntry['rank'];
        }

        if ($user->has_suspicious_game_results) {
            return null;
        }

        $higherRankedUsersCount = $this->leaderboardEligibleUsers()
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('best_score', '>', $user->best_score)
                    ->orWhere(function (Builder $tieBreakerQuery) use ($user): void {
                        $tieBreakerQuery
                            ->where('best_score', $user->best_score)
                            ->where('id', '<', $user->id);
                    });
            })
            ->count();

        return $higherRankedUsersCount + 1;
    }

    /**
     * @return array{
     *     entries: Collection<int, User>,
     *     current_user_rank?: ?int,
     *     current_user_score?: int
     * }
     */
    public function getLeaderboardData(?User $user = null): array
    {
        $payload = [
            'entries' => $this->getTopEntries(),
        ];

        // Only include user-specific fields when a valid authenticated user exists.
        // Guest responses must remain strictly public-safe.
        if ($user?->exists) {
            $payload['current_user_rank'] = $this->getCurrentUserRank($user);
            $payload['current_user_score'] = $this->getCurrentUserScoreForLeaderboard($user);
        }

        return $payload;
    }

    /**
     * @return array<int, array{user_id:int,rank:int,best_score:int,email:string,masked_email:string}>
     */
    public function buildFrozenSnapshotEntries(): array
    {
        return $this->leaderboardEligibleUsers()
            ->select(['id', 'email', 'best_score'])
            ->orderByDesc('best_score')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(fn (User $user, int $index): array => [
                'user_id' => $user->id,
                'rank' => $index + 1,
                'best_score' => (int) $user->best_score,
                'email' => $user->email,
                'masked_email' => $this->maskEmail($user->email),
            ])
            ->all();
    }

    protected function leaderboardEligibleUsers(): Builder
    {
        return User::query()->where('has_suspicious_game_results', false);
    }

    protected function getCurrentUserScoreForLeaderboard(User $user): int
    {
        $frozenEntry = $this->getFrozenSnapshotEntryForUser($user);

        if ($frozenEntry !== null) {
            return (int) $frozenEntry['best_score'];
        }

        return (int) $user->best_score;
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return Collection<int, User>
     */
    protected function mapSnapshotEntriesToUsers(array $entries, int $limit): Collection
    {
        return collect($entries)
            ->take($limit)
            ->values()
            ->map(function (array $entry): User {
                $user = new User();
                $user->forceFill([
                    'id' => (int) ($entry['user_id'] ?? 0),
                    'email' => (string) ($entry['email'] ?? ''),
                    'best_score' => (int) ($entry['best_score'] ?? 0),
                ]);
                $user->setAttribute('rank', (int) ($entry['rank'] ?? 0));
                $user->setAttribute('masked_email', (string) ($entry['masked_email'] ?? $this->maskEmail((string) ($entry['email'] ?? ''))));

                return $user;
            });
    }

    /**
     * @return array{user_id:int,rank:int,best_score:int,email:string,masked_email:string}|null
     */
    protected function getFrozenSnapshotEntryForUser(User $user): ?array
    {
        $frozenSnapshot = $this->frozenLeaderboardService->getActiveSnapshot();

        if ($frozenSnapshot === null) {
            return null;
        }

        foreach ((array) data_get($frozenSnapshot->payload, 'entries', []) as $entry) {
            if ((int) ($entry['user_id'] ?? 0) === (int) $user->id) {
                return $entry;
            }
        }

        return null;
    }

    public function maskEmail(string $email): string
    {
        $segments = explode('@', $email, 2);
        $local = $segments[0] ?? '';
        $domain = $segments[1] ?? '';

        $visiblePart = mb_substr($local, 0, min(2, mb_strlen($local)));
        $maskedLocal = $visiblePart.'***';

        if ($domain === '') {
            return $maskedLocal;
        }

        return $maskedLocal.'@'.$domain;
    }
}
