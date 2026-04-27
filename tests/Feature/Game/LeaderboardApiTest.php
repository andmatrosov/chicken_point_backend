<?php

namespace Tests\Feature\Game;

use App\Models\Prize;
use App\Models\User;
use App\Services\FrozenLeaderboardService;
use App\Services\PrizeAutoAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_returns_the_top_fifteen_entries_in_score_order(): void
    {
        config()->set('game.leaderboard.size', 15);

        for ($score = 20; $score >= 1; $score--) {
            User::factory()->create([
                'email' => "player{$score}@example.com",
                'best_score' => $score * 10,
            ]);
        }

        User::factory()->create([
            'email' => 'viewer@example.com',
            'best_score' => 55,
        ]);

        $response = $this->getJson('/api/game/leaderboard');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(15, 'data.entries')
            ->assertJsonPath('data.entries.0.rank', 1)
            ->assertJsonPath('data.entries.0.score', 200)
            ->assertJsonPath('data.entries.14.rank', 15)
            ->assertJsonPath('data.entries.14.score', 60)
            ->assertJsonMissingPath('data.current_user_rank')
            ->assertJsonMissingPath('data.current_user_score');

        $scores = array_column($response->json('data.entries'), 'score');

        $this->assertSame($scores, array_values($scores));
        $this->assertSame([200, 190, 180, 170, 160, 150, 140, 130, 120, 110, 100, 90, 80, 70, 60], $scores);
    }

    public function test_leaderboard_returns_ranked_masked_entries_and_current_user_rank(): void
    {
        $third = User::factory()->create([
            'email' => 'third@example.com',
            'best_score' => 700,
        ]);

        $first = User::factory()->create([
            'email' => 'alpha@example.com',
            'best_score' => 1200,
        ]);

        $second = User::factory()->create([
            'email' => 'bravo@example.com',
            'best_score' => 1200,
        ]);

        $currentUser = User::factory()->create([
            'email' => 'current@example.com',
            'best_score' => 800,
        ]);

        $plainTextToken = $currentUser->createToken('mobile-client')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/game/leaderboard');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(4, 'data.entries')
            ->assertJsonPath('data.entries.0.rank', 1)
            ->assertJsonPath('data.entries.0.score', 1200)
            ->assertJsonPath('data.entries.0.masked_email', 'al***@example.com')
            ->assertJsonPath('data.entries.1.rank', 2)
            ->assertJsonPath('data.entries.1.score', 1200)
            ->assertJsonPath('data.entries.1.masked_email', 'br***@example.com')
            ->assertJsonPath('data.entries.2.rank', 3)
            ->assertJsonPath('data.entries.2.score', 800)
            ->assertJsonPath('data.entries.2.masked_email', 'cu***@example.com')
            ->assertJsonPath('data.current_user_rank', 3)
            ->assertJsonPath('data.current_user_score', 800);

        $this->assertNotSame('alpha@example.com', $response->json('data.entries.0.masked_email'));
        $this->assertNotSame('bravo@example.com', $response->json('data.entries.1.masked_email'));
        $this->assertNotSame('current@example.com', $response->json('data.entries.2.masked_email'));

        $this->assertTrue($third->id > 0);
    }

    public function test_leaderboard_is_limited_by_configured_size(): void
    {
        config()->set('game.leaderboard.size', 3);

        User::factory()->count(5)->sequence(
            ['email' => 'one@example.com', 'best_score' => 500],
            ['email' => 'two@example.com', 'best_score' => 400],
            ['email' => 'three@example.com', 'best_score' => 300],
            ['email' => 'four@example.com', 'best_score' => 200],
            ['email' => 'five@example.com', 'best_score' => 100],
        )->create();

        $currentUser = User::factory()->create([
            'email' => 'viewer@example.com',
            'best_score' => 50,
        ]);

        $plainTextToken = $currentUser->createToken('mobile-client')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/game/leaderboard');

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data.entries')
            ->assertJsonPath('data.entries.0.score', 500)
            ->assertJsonPath('data.entries.1.score', 400)
            ->assertJsonPath('data.entries.2.score', 300)
            ->assertJsonPath('data.current_user_rank', 6)
            ->assertJsonPath('data.current_user_score', 50);
    }

    public function test_guest_leaderboard_response_contains_only_public_data(): void
    {
        User::factory()->count(3)->sequence(
            ['email' => 'first@example.com', 'best_score' => 900],
            ['email' => 'second@example.com', 'best_score' => 800],
            ['email' => 'third@example.com', 'best_score' => 700],
        )->create();

        $response = $this->getJson('/api/game/leaderboard');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.entries')
            ->assertJsonMissingPath('data.current_user_rank')
            ->assertJsonMissingPath('data.current_user_score')
            ->assertJsonMissingPath('data.entries.0.email');
    }

    public function test_flagged_users_are_excluded_from_leaderboard_and_receive_null_rank(): void
    {
        User::factory()->create([
            'email' => 'flagged-top@example.com',
            'best_score' => 1400,
            'has_suspicious_game_results' => true,
        ]);

        User::factory()->create([
            'email' => 'clean-top@example.com',
            'best_score' => 1200,
        ]);

        $flaggedUser = User::factory()->create([
            'email' => 'flagged-viewer@example.com',
            'best_score' => 1100,
            'has_suspicious_game_results' => true,
        ]);

        $plainTextToken = $flaggedUser->createToken('mobile-client')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/game/leaderboard');

        $response
            ->assertOk()
            ->assertJsonPath('data.entries.0.score', 1200)
            ->assertJsonCount(1, 'data.entries')
            ->assertJsonPath('data.current_user_rank', null)
            ->assertJsonPath('data.current_user_score', 1100);
    }

    public function test_users_with_points_below_threshold_stay_in_leaderboard(): void
    {
        User::factory()->create([
            'email' => 'points-user@example.com',
            'best_score' => 1100,
            'suspicious_game_result_points' => 2,
            'has_suspicious_game_results' => false,
        ]);

        $response = $this->getJson('/api/game/leaderboard');

        $response
            ->assertOk()
            ->assertJsonPath('data.entries.0.score', 1100)
            ->assertJsonCount(1, 'data.entries');
    }

    public function test_leaderboard_is_frozen_after_prize_assignment_until_it_is_cleared(): void
    {
        config()->set('game.leaderboard.size', 2);
        config()->set('game.prizes.use_remaining_stock', true);

        $admin = User::factory()->create(['is_admin' => true]);
        $first = User::factory()->create([
            'email' => 'alpha@example.com',
            'best_score' => 1000,
        ]);
        $second = User::factory()->create([
            'email' => 'bravo@example.com',
            'best_score' => 900,
        ]);
        $viewer = User::factory()->create([
            'email' => 'viewer@example.com',
            'best_score' => 800,
        ]);

        Prize::query()->create([
            'title' => 'Rank 1 Prize',
            'description' => 'Rank 1 reward.',
            'quantity' => 1,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        Prize::query()->create([
            'title' => 'Rank 2 Prize',
            'description' => 'Rank 2 reward.',
            'quantity' => 1,
            'default_rank_from' => 2,
            'default_rank_to' => 2,
            'is_active' => true,
        ]);

        app(PrizeAutoAssignmentService::class)->assignCurrentLeaderboardPrizes($admin);

        $second->forceFill(['best_score' => 1300])->save();
        $viewer->forceFill(['best_score' => 1200])->save();

        $plainTextToken = $viewer->createToken('mobile-client')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/game/leaderboard')
            ->assertOk()
            ->assertJsonPath('data.entries.0.score', 1000)
            ->assertJsonPath('data.entries.1.score', 900)
            ->assertJsonPath('data.entries.0.masked_email', 'al***@example.com')
            ->assertJsonPath('data.entries.1.masked_email', 'br***@example.com')
            ->assertJsonPath('data.current_user_rank', 3)
            ->assertJsonPath('data.current_user_score', 800);

        $this->assertTrue(app(FrozenLeaderboardService::class)->clear($admin));

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/game/leaderboard')
            ->assertOk()
            ->assertJsonPath('data.entries.0.score', 1300)
            ->assertJsonPath('data.entries.1.score', 1200)
            ->assertJsonPath('data.current_user_rank', 2)
            ->assertJsonPath('data.current_user_score', 1200);

        $this->assertTrue($first->id > 0);
    }
}
