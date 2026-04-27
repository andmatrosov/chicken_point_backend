<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserSuspiciousEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetSuspiciousResultsCommand extends Command
{
    protected $signature = 'game:reset-suspicious-results
        {--dry-run : Только посчитать без записи в БД}
        {--user_id= : Сбросить только для одного пользователя}';

    protected $description = 'Reset suspicious game result flags, points, and stored suspicious events.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $userId = $this->option('user_id');

        $userQuery = User::query()
            ->when($userId !== null, fn ($query) => $query->where('id', (int) $userId));

        $eventQuery = UserSuspiciousEvent::query()
            ->when($userId !== null, fn ($query) => $query->where('user_id', (int) $userId));

        $usersToReset = (clone $userQuery)
            ->where(function ($query): void {
                $query->where('suspicious_game_result_points', '>', 0)
                    ->orWhere('has_suspicious_game_results', true)
                    ->orWhereNotNull('suspicious_game_results_flagged_at')
                    ->orWhereNotNull('suspicious_game_results_reason');
            })
            ->count();

        $eventsToDelete = (clone $eventQuery)->count();

        if (! $dryRun) {
            DB::transaction(function () use ($userQuery, $eventQuery): void {
                $userQuery->update([
                    'suspicious_game_result_points' => 0,
                    'has_suspicious_game_results' => false,
                    'suspicious_game_results_flagged_at' => null,
                    'suspicious_game_results_reason' => null,
                ]);

                $eventQuery->delete();
            });
        }

        $this->line(sprintf('Users reset: %d', $usersToReset));
        $this->line(sprintf('Suspicious events cleared: %d', $eventsToDelete));
        $this->line(sprintf('Mode: %s', $dryRun ? 'DRY RUN (no changes applied)' : 'APPLY'));

        return self::SUCCESS;
    }
}
