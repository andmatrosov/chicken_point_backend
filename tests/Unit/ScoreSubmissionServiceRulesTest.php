<?php

namespace Tests\Unit;

use App\Exceptions\BusinessException;
use App\Models\GameSession;
use App\Models\User;
use App\Services\ScoreSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreSubmissionServiceRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_scores_outside_the_allowed_range(): void
    {
        $user = User::factory()->create();

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('The submitted score is outside the allowed range.');

        app(ScoreSubmissionService::class)->validateScore($user, 'session-token', 1000001);
    }

    public function test_it_rejects_invalid_duration_metadata(): void
    {
        $user = User::factory()->create();
        $gameSession = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'duration-validation-session',
            'status' => \App\Enums\GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('The submitted metadata is invalid.');

        app(ScoreSubmissionService::class)->validateSubmissionMetadata(
            $user,
            'session-token',
            $gameSession,
            [
                'duration' => 1,
            ],
        );
    }

    public function test_it_rejects_collected_coins_above_the_allowed_range(): void
    {
        $user = User::factory()->create();

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('The submitted collected coin value is outside the allowed range.');

        app(ScoreSubmissionService::class)->validateCollectedCoins(
            $user,
            'session-token',
            1200,
            (int) config('game.score_validation.max_coins_collected_per_run') + 1,
            ['duration' => 120],
        );
    }

    public function test_it_accepts_collected_coins_within_the_allowed_range(): void
    {
        $user = User::factory()->create();

        app(ScoreSubmissionService::class)->validateCollectedCoins(
            $user,
            'session-token',
            1200,
            (int) config('game.score_validation.max_coins_collected_per_run'),
            ['duration' => 120],
        );

        $this->assertTrue(true);
    }

    public function test_it_accepts_server_safe_submission_metadata_without_client_coin_field(): void
    {
        $user = User::factory()->create();
        $gameSession = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'safe-metadata-session',
            'status' => \App\Enums\GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        app(ScoreSubmissionService::class)->validateSubmissionMetadata(
            $user,
            'session-token',
            $gameSession,
            [
                'duration' => 120,
                'device_id' => 'ios-device-1',
                'app_version' => '1.0.0',
            ],
        );

        $this->assertTrue(true);
    }

    public function test_it_merges_submission_metadata_into_existing_session_metadata(): void
    {
        $user = User::factory()->create();

        $gameSession = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'merge-metadata-session',
            'status' => \App\Enums\GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'metadata' => [
                'device_id' => 'ios-device-1',
            ],
        ]);

        $metadata = app(ScoreSubmissionService::class)->mergeSessionMetadata($gameSession, [
            'duration' => 120,
            'device_id' => 'ios-device-1',
        ]);

        $this->assertSame('ios-device-1', $metadata['device_id']);
        $this->assertSame(120, $metadata['submission']['duration']);
        $this->assertSame('ios-device-1', $metadata['submission']['device_id']);
    }
}
