<?php

namespace Tests\Feature\Console;

use App\Enums\GameSessionStatus;
use App\Models\GameScore;
use App\Models\GameSession;
use App\Models\User;
use App\Models\UserSuspiciousEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecalculateSuspiciousResultsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculate_suspicious_results_dry_run_does_not_change_data(): void
    {
        $user = User::factory()->create();

        $this->createSubmittedScore(
            user: $user,
            token: 'soft-dry-run-score',
            score: 140,
            issuedAt: now()->subSeconds(30),
            createdAt: now(),
        );

        $this->artisan('game:recalculate-suspicious-results', ['--dry-run' => true])
            ->expectsOutput('Processed: 1 scores')
            ->expectsOutput('Unreliable duration: 0')
            ->expectsOutput('Suspicious: 1')
            ->expectsOutput('Cheat suspicious (reliable only): 1')
            ->expectsOutput('  Hard: 0')
            ->expectsOutput('  Soft velocity: 1')
            ->expectsOutput('  Duration mismatch: 0')
            ->expectsOutput('  Combined signals: 0')
            ->expectsOutput('  Timing only: 0')
            ->expectsOutput('Skipped due to unreliable timing: 0')
            ->expectsOutput('Users affected: 1')
            ->expectsOutput('Users flagged: 0')
            ->expectsOutput('Top suspicious:')
            ->expectsOutput('Mode: DRY RUN (no changes applied)')
            ->assertSuccessful();

        $this->assertSame(0, $user->fresh()->suspicious_game_result_points);
        $this->assertFalse((bool) $user->fresh()->has_suspicious_game_results);
        $this->assertDatabaseCount('user_suspicious_events', 0);
    }

    public function test_recalculate_suspicious_results_applies_points_and_flags_user_at_threshold(): void
    {
        $user = User::factory()->create();

        $baseTime = now()->subHour();

        $this->createSubmittedScore($user, 'soft-1', 140, $baseTime->copy(), $baseTime->copy()->addSeconds(30));
        $this->createSubmittedScore($user, 'soft-2', 140, $baseTime->copy()->addMinutes(2), $baseTime->copy()->addMinutes(2)->addSeconds(30));
        $this->createSubmittedScore($user, 'soft-3', 140, $baseTime->copy()->addMinutes(4), $baseTime->copy()->addMinutes(4)->addSeconds(30));

        $this->artisan('game:recalculate-suspicious-results')
            ->expectsOutput('Processed: 3 scores')
            ->expectsOutput('Unreliable duration: 0')
            ->expectsOutput('Suspicious: 3')
            ->expectsOutput('Cheat suspicious (reliable only): 3')
            ->expectsOutput('  Hard: 0')
            ->expectsOutput('  Soft velocity: 3')
            ->expectsOutput('  Duration mismatch: 0')
            ->expectsOutput('  Combined signals: 0')
            ->expectsOutput('  Timing only: 0')
            ->expectsOutput('Skipped due to unreliable timing: 0')
            ->expectsOutput('Users affected: 1')
            ->expectsOutput('Users flagged: 1')
            ->expectsOutput('Mode: APPLY')
            ->assertSuccessful();

        $user->refresh();

        $this->assertSame(3, $user->suspicious_game_result_points);
        $this->assertTrue((bool) $user->has_suspicious_game_results);
        $this->assertSame('high_score_velocity', $user->suspicious_game_results_reason);
        $this->assertDatabaseCount('user_suspicious_events', 3);
    }

    public function test_recalculate_suspicious_results_is_idempotent(): void
    {
        $user = User::factory()->create();

        $score = $this->createSubmittedScore(
            user: $user,
            token: 'hard-1',
            score: 1016,
            issuedAt: now()->subSeconds(240),
            createdAt: now(),
        );

        $this->artisan('game:recalculate-suspicious-results')->assertSuccessful();

        $user->refresh();

        $this->assertSame(4, $user->suspicious_game_result_points);
        $this->assertTrue((bool) $user->has_suspicious_game_results);
        $this->assertDatabaseHas('user_suspicious_events', [
            'game_score_id' => $score->id,
            'points' => 4,
            'reason' => 'adaptive_score_limit_exceeded',
        ]);

        $this->artisan('game:recalculate-suspicious-results')
            ->expectsOutput('Processed: 1 scores')
            ->expectsOutput('Unreliable duration: 0')
            ->expectsOutput('Suspicious: 0')
            ->expectsOutput('Cheat suspicious (reliable only): 0')
            ->expectsOutput('  Hard: 0')
            ->expectsOutput('  Soft velocity: 0')
            ->expectsOutput('  Duration mismatch: 0')
            ->expectsOutput('  Combined signals: 0')
            ->expectsOutput('  Timing only: 0')
            ->expectsOutput('Skipped due to unreliable timing: 0')
            ->expectsOutput('Mode: APPLY')
            ->assertSuccessful();

        $this->assertSame(4, $user->fresh()->suspicious_game_result_points);
        $this->assertDatabaseCount('user_suspicious_events', 1);
    }

    public function test_reset_suspicious_results_clears_points_flags_and_events(): void
    {
        $user = User::factory()->create([
            'suspicious_game_result_points' => 4,
            'has_suspicious_game_results' => true,
            'suspicious_game_results_flagged_at' => now(),
            'suspicious_game_results_reason' => 'adaptive_score_limit_exceeded',
        ]);

        $score = GameScore::query()->create([
            'user_id' => $user->id,
            'score' => 500,
            'coins_collected' => 0,
            'session_token' => 'reset-score',
            'is_processed' => true,
        ]);

        UserSuspiciousEvent::query()->create([
            'user_id' => $user->id,
            'game_score_id' => $score->id,
            'reason' => 'adaptive_score_limit_exceeded',
            'points' => 3,
        ]);

        $this->artisan('game:reset-suspicious-results')
            ->expectsOutput('Users reset: 1')
            ->expectsOutput('Suspicious events cleared: 1')
            ->expectsOutput('Mode: APPLY')
            ->assertSuccessful();

        $user->refresh();

        $this->assertSame(0, $user->suspicious_game_result_points);
        $this->assertFalse((bool) $user->has_suspicious_game_results);
        $this->assertNull($user->suspicious_game_results_flagged_at);
        $this->assertNull($user->suspicious_game_results_reason);
        $this->assertDatabaseCount('user_suspicious_events', 0);
    }

    public function test_historical_recalculation_tracks_duration_mismatch_without_points(): void
    {
        $user = User::factory()->create();

        $issuedAt = now()->subSecond();
        $createdAt = now();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'historical-duration-mismatch',
            'status' => GameSessionStatus::SUBMITTED,
            'issued_at' => $issuedAt,
            'submitted_at' => $createdAt,
            'expires_at' => null,
            'metadata' => [
                'submission' => [
                    'duration' => 240,
                ],
            ],
        ]);

        GameScore::query()->forceCreate([
            'user_id' => $user->id,
            'score' => 40,
            'coins_collected' => 0,
            'session_token' => 'historical-duration-mismatch',
            'is_processed' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $this->artisan('game:recalculate-suspicious-results')
            ->expectsOutput('Processed: 1 scores')
            ->expectsOutput('Unreliable duration: 1')
            ->expectsOutput('Suspicious: 1')
            ->expectsOutput('Cheat suspicious (reliable only): 0')
            ->expectsOutput('  Duration mismatch: 1')
            ->expectsOutput('  Timing only: 1')
            ->expectsOutput('Skipped due to unreliable timing: 1')
            ->assertSuccessful();

        $this->assertSame(0, $user->fresh()->suspicious_game_result_points);
        $this->assertDatabaseHas('user_suspicious_events', [
            'user_id' => $user->id,
            'reason' => 'unreliable_server_duration',
            'points' => 0,
        ]);
    }

    protected function createSubmittedScore(
        User $user,
        string $token,
        int $score,
        Carbon $issuedAt,
        Carbon $createdAt,
    ): GameScore {
        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => $token,
            'status' => GameSessionStatus::SUBMITTED,
            'issued_at' => $issuedAt,
            'submitted_at' => $createdAt,
            'expires_at' => null,
        ]);

        return GameScore::query()->forceCreate([
            'user_id' => $user->id,
            'score' => $score,
            'coins_collected' => 0,
            'session_token' => $token,
            'is_processed' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
