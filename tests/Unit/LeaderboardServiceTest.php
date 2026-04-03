<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_masks_email_consistently(): void
    {
        $leaderboardService = app(LeaderboardService::class);

        $this->assertSame('al***@example.com', $leaderboardService->maskEmail('alex@example.com'));
        $this->assertSame('a***@example.com', $leaderboardService->maskEmail('a@example.com'));
    }

    public function test_it_calculates_rank_with_id_as_tie_breaker(): void
    {
        $first = User::factory()->create([
            'best_score' => 1000,
        ]);

        $second = User::factory()->create([
            'best_score' => 1000,
        ]);

        $leaderboardService = app(LeaderboardService::class);

        $this->assertSame(1, $leaderboardService->getCurrentUserRank($first));
        $this->assertSame(2, $leaderboardService->getCurrentUserRank($second));
    }
}
