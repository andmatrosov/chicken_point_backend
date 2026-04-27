<?php

namespace App\Console\Commands;

use App\Data\Game\ScoreSuspicionResult;
use App\Enums\GameSessionStatus;
use App\Models\GameScore;
use App\Models\User;
use App\Models\UserSuspiciousEvent;
use App\Services\ScoreSubmissionService;
use App\Services\SecurityEventLogger;
use App\Services\SuspiciousGameResultFlagService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecalculateSuspiciousResultsCommand extends Command
{
    protected $signature = 'game:recalculate-suspicious-results
        {--dry-run : Только посчитать без записи в БД}
        {--user_id= : Пересчитать только для одного пользователя}
        {--from_id= : Начать с конкретного game_score id}
        {--chunk=1000 : Размер чанка}';

    protected $description = 'Recalculate suspicious historical game score results with the current anti-cheat model.';

    /**
     * @var array<int, int>
     */
    protected array $projectedPointsByUser = [];

    /**
     * @var array<int, bool>
     */
    protected array $affectedUsers = [];

    /**
     * @var array<int, bool>
     */
    protected array $flaggedUsers = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $topSuspiciousExamples = [];

    public function __construct(
        protected ScoreSubmissionService $scoreSubmissionService,
        protected SuspiciousGameResultFlagService $suspiciousGameResultFlagService,
        protected SecurityEventLogger $securityEventLogger,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $userId = $this->option('user_id');
        $fromId = $this->option('from_id');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $stats = [
            'processed' => 0,
            'suspicious_total' => 0,
            'unreliable_duration' => 0,
            'hard' => 0,
            'cheat_reliable_only' => 0,
            'soft_velocity' => 0,
            'duration_mismatch' => 0,
            'combined' => 0,
            'timing_only' => 0,
            'skipped_due_unreliable_timing' => 0,
        ];

        GameScore::query()
            ->select('game_scores.*')
            ->join('game_sessions', 'game_sessions.token', '=', 'game_scores.session_token')
            ->with([
                'user:id,email,has_suspicious_game_results,suspicious_game_result_points,suspicious_game_results_flagged_at,suspicious_game_results_reason',
                'gameSession:id,user_id,token,status,issued_at,submitted_at,metadata',
                'suspiciousEvent:id,game_score_id,reason,points,signals,context',
            ])
            ->where('game_scores.is_processed', true)
            ->where('game_sessions.status', GameSessionStatus::SUBMITTED->value)
            ->when($userId !== null, fn ($query) => $query->where('game_scores.user_id', (int) $userId))
            ->when($fromId !== null, fn ($query) => $query->where('game_scores.id', '>=', (int) $fromId))
            ->orderBy('game_scores.id')
            ->chunkById($chunkSize, function ($scores) use (&$stats, $dryRun): void {
                foreach ($scores as $score) {
                    $stats['processed']++;

                    try {
                        $this->processScore($score, $dryRun, $stats);
                    } catch (Throwable $throwable) {
                        $this->securityEventLogger->logBusinessFailure('recalculate_suspicious_results_failed', [
                            'game_score_id' => $score->id,
                            'user_id' => $score->user_id,
                            'error' => $throwable->getMessage(),
                        ]);

                        $this->warn(sprintf(
                            'Skipped game_score #%d: %s',
                            $score->id,
                            $throwable->getMessage(),
                        ));
                    }
                }
            }, 'game_scores.id', 'id');

        $this->line(sprintf('Processed: %d scores', $stats['processed']));
        $this->line(sprintf('Unreliable duration: %d', $stats['unreliable_duration']));
        $this->line(sprintf('Suspicious: %d', $stats['suspicious_total']));
        $this->line(sprintf('Cheat suspicious (reliable only): %d', $stats['cheat_reliable_only']));
        $this->line(sprintf('  Hard: %d', $stats['hard']));
        $this->line(sprintf('  Soft velocity: %d', $stats['soft_velocity']));
        $this->line(sprintf('  Duration mismatch: %d', $stats['duration_mismatch']));
        $this->line(sprintf('  Combined signals: %d', $stats['combined']));
        $this->line(sprintf('  Timing only: %d', $stats['timing_only']));
        $this->line(sprintf('Skipped due to unreliable timing: %d', $stats['skipped_due_unreliable_timing']));
        $this->line(sprintf('Users affected: %d', count($this->affectedUsers)));
        $this->line(sprintf('Users flagged: %d', count($this->flaggedUsers)));

        if ($this->topSuspiciousExamples !== []) {
            $this->line('Top suspicious:');

            foreach (array_slice($this->sortedTopExamples(), 0, 5) as $example) {
                $this->line(sprintf(
                    '- user_id=%d email=%s score=%d server_runtime=%ss server_historical=%ss client=%s sps_runtime=%s sps_historical=%s client_sps=%s reasons=%s points=%d',
                    $example['user_id'],
                    $example['email'],
                    $example['score'],
                    $example['server_duration_runtime'],
                    $example['server_duration_historical'],
                    $example['client_duration'] === null ? 'n/a' : $example['client_duration'].'s',
                    $example['server_sps_runtime'],
                    $example['server_sps_historical'],
                    $example['client_sps'] ?? 'n/a',
                    implode(',', $example['reasons']),
                    $example['points'],
                ));
            }
        }

        $this->line(sprintf('Mode: %s', $dryRun ? 'DRY RUN (no changes applied)' : 'APPLY'));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $stats
     */
    protected function processScore(GameScore $score, bool $dryRun, array &$stats): void
    {
        if ($score->relationLoaded('suspiciousEvent') && $score->suspiciousEvent !== null) {
            return;
        }

        if ($score->user === null || $score->gameSession === null || $score->created_at === null) {
            return;
        }

        $submittedAt = Carbon::instance($score->created_at);
        $result = $this->scoreSubmissionService->detectSuspiciousScoreSubmission(
            $score->gameSession,
            $score->score,
            $submittedAt,
        );

        if (! $result->isSuspicious) {
            return;
        }

        $stats['suspicious_total']++;
        $stats['unreliable_duration'] += $this->hasSignal($result, 'unreliable_server_duration') ? 1 : 0;
        $stats['cheat_reliable_only'] += $this->hasCheatSignal($result) ? 1 : 0;
        $stats['hard'] += $result->isHardSuspicious ? 1 : 0;
        $stats['soft_velocity'] += $this->hasSignal($result, 'high_score_velocity') ? 1 : 0;
        $stats['duration_mismatch'] += $this->hasSignal($result, 'duration_mismatch') ? 1 : 0;
        $stats['combined'] += count($result->signals) > 1 ? 1 : 0;
        $stats['timing_only'] += ! $this->hasCheatSignal($result) && $this->hasTimingSignal($result) ? 1 : 0;
        $stats['skipped_due_unreliable_timing'] += $this->hasSignal($result, 'unreliable_server_duration') ? 1 : 0;
        $this->affectedUsers[$score->user_id] = true;
        $this->rememberTopExample($score, $result);

        if ($dryRun) {
            $this->applyProjectedStats($score->user, $result);
            $this->logRecalculatedSuspiciousScore($score, $result, true, $this->projectedPointsByUser[$score->user_id]);

            return;
        }

        $outcome = DB::transaction(function () use ($score, $result): ?array {
            $event = UserSuspiciousEvent::query()->firstOrCreate(
                ['game_score_id' => $score->id],
                [
                    'user_id' => $score->user_id,
                    'reason' => $result->reason,
                    'points' => $result->points,
                    'signals' => $result->signals,
                    'context' => $result->context,
                ],
            );

            if (! $event->wasRecentlyCreated) {
                return null;
            }

            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($score->user_id);

            $wasFlagged = (bool) $user->has_suspicious_game_results;

            $this->suspiciousGameResultFlagService->addSuspicionPoints(
                $user,
                $result->points,
                $result->reason,
                $result->context,
            );

            $user->refresh();

            return [
                'total_points_after' => (int) $user->suspicious_game_result_points,
                'newly_flagged' => ! $wasFlagged && (bool) $user->has_suspicious_game_results,
            ];
        });

        if ($outcome === null) {
            return;
        }

        if ($outcome['newly_flagged'] === true) {
            $this->flaggedUsers[$score->user_id] = true;
        }

        $this->logRecalculatedSuspiciousScore($score, $result, false, (int) $outcome['total_points_after']);
    }

    protected function applyProjectedStats(User $user, ScoreSuspicionResult $result): void
    {
        $currentPoints = $this->projectedPointsByUser[$user->id] ?? (int) $user->suspicious_game_result_points;
        $newPoints = $currentPoints + $result->points;

        $this->projectedPointsByUser[$user->id] = $newPoints;

        if (! $user->has_suspicious_game_results && $newPoints >= $this->flagThreshold()) {
            $this->flaggedUsers[$user->id] = true;
        }
    }

    protected function logRecalculatedSuspiciousScore(
        GameScore $score,
        ScoreSuspicionResult $result,
        bool $dryRun,
        int $totalPointsAfter,
    ): void {
        $this->securityEventLogger->logRecalculatedSuspiciousScore(
            userId: $score->user_id,
            gameScoreId: $score->id,
            score: $score->score,
            context: array_merge($result->context, [
                'points_added' => $result->points,
                'total_points_after' => $totalPointsAfter,
                'reason' => $result->reason,
                'reasons' => array_map(
                    static fn (array $signal): string => (string) ($signal['reason'] ?? 'unknown'),
                    $result->signals,
                ),
                'signals' => $result->signals,
                'dry_run' => $dryRun,
            ]),
        );
    }

    protected function hasSignal(ScoreSuspicionResult $result, string $reason): bool
    {
        foreach ($result->signals as $signal) {
            if (($signal['reason'] ?? null) === $reason) {
                return true;
            }
        }

        return false;
    }

    protected function hasCheatSignal(ScoreSuspicionResult $result): bool
    {
        foreach ($result->signals as $signal) {
            if (($signal['category'] ?? 'cheat') === 'cheat') {
                return true;
            }
        }

        return false;
    }

    protected function hasTimingSignal(ScoreSuspicionResult $result): bool
    {
        foreach ($result->signals as $signal) {
            if (($signal['category'] ?? 'cheat') === 'timing') {
                return true;
            }
        }

        return false;
    }

    protected function rememberTopExample(GameScore $score, ScoreSuspicionResult $result): void
    {
        $serverDurationRuntime = (int) ($result->context['server_duration_runtime_style'] ?? $result->context['server_duration'] ?? $result->context['elapsed_seconds'] ?? 0);
        $serverDurationHistorical = $score->gameSession !== null
            ? ($this->scoreSubmissionService->calculateHistoricalServerElapsedSeconds($score->gameSession, $score) ?? 0)
            : 0;
        $clientDuration = $result->context['client_duration'] ?? null;

        $this->topSuspiciousExamples[] = [
            'user_id' => $score->user_id,
            'email' => $score->user?->email ?? 'unknown',
            'score' => $score->score,
            'server_duration_runtime' => $serverDurationRuntime,
            'server_duration_historical' => $serverDurationHistorical,
            'client_duration' => is_int($clientDuration) ? $clientDuration : null,
            'server_sps_runtime' => $result->context['score_per_second'] ?? null,
            'server_sps_historical' => $serverDurationHistorical > 0
                ? round($score->score / $serverDurationHistorical, 4)
                : null,
            'client_sps' => is_int($clientDuration) && $clientDuration > 0
                ? round($score->score / $clientDuration, 4)
                : null,
            'reasons' => array_map(
                static fn (array $signal): string => (string) ($signal['reason'] ?? 'unknown'),
                $result->signals,
            ),
            'points' => $result->points,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function sortedTopExamples(): array
    {
        $examples = $this->topSuspiciousExamples;

        usort($examples, static function (array $left, array $right): int {
            return [$right['points'], $right['server_sps_runtime'] ?? 0, $right['score']]
                <=> [$left['points'], $left['server_sps_runtime'] ?? 0, $left['score']];
        });

        return $examples;
    }

    protected function flagThreshold(): int
    {
        return max(1, (int) config('game.anti_cheat.suspicion_points_to_flag', 3));
    }
}
