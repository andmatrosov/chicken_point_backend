<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LeaderboardService
{
    /**
     * @return Collection<int, User>
     */
    public function getTopEntries(?int $limit = null): Collection
    {
        $size = $limit ?? (int) config('game.leaderboard.size', 15);

        return User::query()
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

    public function getCurrentUserRank(User $user): int
    {
        $higherRankedUsersCount = User::query()
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
     *     current_user_rank: int|null,
     *     current_user_score: int|null
     * }
     */
    public function getLeaderboardData(?User $user = null): array
    {
        return [
            'entries' => $this->getTopEntries(),
            'current_user_rank' => $user?->exists ? $this->getCurrentUserRank($user) : null,
            'current_user_score' => $user?->best_score,
        ];
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
