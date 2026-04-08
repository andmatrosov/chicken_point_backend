<?php

namespace Tests\Unit;

use App\Models\GameScore;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameScoreIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_rejects_duplicate_game_scores_for_the_same_session_token(): void
    {
        $user = User::factory()->create();

        GameScore::query()->create([
            'user_id' => $user->id,
            'score' => 150,
            'session_token' => 'duplicate-score-session-token',
            'is_processed' => true,
        ]);

        $this->expectException(QueryException::class);

        GameScore::query()->create([
            'user_id' => $user->id,
            'score' => 300,
            'session_token' => 'duplicate-score-session-token',
            'is_processed' => true,
        ]);
    }

    public function test_game_score_defaults_collected_coins_to_zero_when_not_provided(): void
    {
        $user = User::factory()->create();

        $score = GameScore::query()->create([
            'user_id' => $user->id,
            'score' => 150,
            'session_token' => 'default-coins-session-token',
            'is_processed' => true,
        ]);

        $this->assertSame(0, $score->fresh()->coins_collected);
    }
}
