<?php

namespace App\Services;

use App\Models\LeaderboardSnapshot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class FrozenLeaderboardService
{
    public const SNAPSHOT_KIND = 'post_prize_assignment';

    public function getActiveSnapshot(): ?LeaderboardSnapshot
    {
        return LeaderboardSnapshot::query()
            ->where('kind', self::SNAPSHOT_KIND)
            ->where('is_active', true)
            ->latest('frozen_at')
            ->first();
    }

    public function hasActiveSnapshot(): bool
    {
        return LeaderboardSnapshot::query()
            ->where('kind', self::SNAPSHOT_KIND)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function freezeFromPrizeSnapshot(array $snapshot, User $admin): LeaderboardSnapshot
    {
        $entries = $this->extractLeaderboardEntries($snapshot);
        $capturedAt = CarbonImmutable::parse((string) ($snapshot['captured_at'] ?? now()->toIso8601String()));
        $sourceHash = is_string($snapshot['leaderboard_hash'] ?? null)
            ? $snapshot['leaderboard_hash']
            : (is_string($snapshot['hash'] ?? null) ? $snapshot['hash'] : null);

        return DB::transaction(function () use ($entries, $capturedAt, $sourceHash, $admin): LeaderboardSnapshot {
            LeaderboardSnapshot::query()
                ->where('kind', self::SNAPSHOT_KIND)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $snapshot = LeaderboardSnapshot::query()->create([
                'kind' => self::SNAPSHOT_KIND,
                'is_active' => true,
                'captured_at' => $capturedAt,
                'source_hash' => $sourceHash,
                'payload' => [
                    'entries' => $entries,
                ],
                'frozen_by_user_id' => $admin->id,
                'frozen_at' => now(),
            ]);

            app(AdminActionLogService::class)->log(
                $admin,
                'freeze_leaderboard_snapshot',
                'leaderboard_snapshot',
                $snapshot->id,
                [
                    'captured_at' => $capturedAt->toIso8601String(),
                    'entries_count' => count($entries),
                    'source_hash' => $sourceHash,
                ],
            );

            return $snapshot;
        });
    }

    public function clear(User $admin): bool
    {
        return DB::transaction(function () use ($admin): bool {
            /** @var LeaderboardSnapshot|null $snapshot */
            $snapshot = LeaderboardSnapshot::query()
                ->where('kind', self::SNAPSHOT_KIND)
                ->where('is_active', true)
                ->lockForUpdate()
                ->latest('frozen_at')
                ->first();

            if ($snapshot === null) {
                return false;
            }

            $snapshot->forceFill([
                'is_active' => false,
                'cleared_by_user_id' => $admin->id,
                'cleared_at' => now(),
            ])->save();

            app(AdminActionLogService::class)->log(
                $admin,
                'clear_frozen_leaderboard_snapshot',
                'leaderboard_snapshot',
                $snapshot->id,
                [
                    'captured_at' => optional($snapshot->captured_at)->toIso8601String(),
                    'entries_count' => count((array) data_get($snapshot->payload, 'entries', [])),
                ],
            );

            return true;
        });
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<int, array{user_id:int,rank:int,best_score:int,email:string,masked_email:string}>
     */
    protected function extractLeaderboardEntries(array $snapshot): array
    {
        $entries = $snapshot['leaderboard_entries'] ?? [];

        if (! is_array($entries)) {
            return [];
        }

        $normalized = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $userId = $entry['user_id'] ?? null;
            $rank = $entry['rank'] ?? null;
            $bestScore = $entry['best_score'] ?? null;
            $email = $entry['email'] ?? null;
            $maskedEmail = $entry['masked_email'] ?? null;

            if (! is_int($userId) || ! is_int($rank) || ! is_int($bestScore) || ! is_string($email) || ! is_string($maskedEmail)) {
                continue;
            }

            $normalized[] = [
                'user_id' => $userId,
                'rank' => $rank,
                'best_score' => $bestScore,
                'email' => $email,
                'masked_email' => $maskedEmail,
            ];
        }

        return $normalized;
    }
}
