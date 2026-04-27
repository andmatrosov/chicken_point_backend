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
            'hard' => 0,
            'soft' => 0,
        ];

        GameScore::query()
            ->select(['id', 'user_id', 'score', 'session_token', 'created_at', 'is_processed'])
            ->with([
                'user:id,has_suspicious_game_results,suspicious_game_result_points,suspicious_game_results_flagged_at,suspicious_game_results_reason',
                'gameSession:id,user_id,token,status,issued_at,submitted_at',
                'suspiciousEvent:id,game_score_id',
            ])
            ->where('is_processed', true)
            ->whereHas('gameSession', fn ($query) => $query->where('status', GameSessionStatus::SUBMITTED->value))
            ->when($userId !== null, fn ($query) => $query->where('user_id', (int) $userId))
            ->when($fromId !== null, fn ($query) => $query->where('id', '>=', (int) $fromId))
            ->orderBy('id')
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
            });

        $this->line(sprintf('Processed: %d scores', $stats['processed']));
        $this->line(sprintf('Suspicious: %d', $stats['suspicious_total']));
        $this->line(sprintf('  Hard: %d', $stats['hard']));
        $this->line(sprintf('  Soft: %d', $stats['soft']));
        $this->line(sprintf('Users affected: %d', count($this->affectedUsers)));
        $this->line(sprintf('Users flagged: %d', count($this->flaggedUsers)));
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
        $stats[$result->isHardSuspicious ? 'hard' : 'soft']++;
        $this->affectedUsers[$score->user_id] = true;

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
                'dry_run' => $dryRun,
            ]),
        );
    }

    protected function flagThreshold(): int
    {
        return max(1, (int) config('game.anti_cheat.suspicion_points_to_flag', 3));
    }
}
