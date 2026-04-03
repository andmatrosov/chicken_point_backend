<?php

namespace Tests\Unit;

use App\Models\Prize;
use App\Models\User;
use App\Services\PrizeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrizeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_prizes_by_rank_using_the_configured_ranges(): void
    {
        $firstPrize = Prize::query()->create([
            'title' => 'First Prize',
            'description' => 'Rank 1 reward.',
            'quantity' => 1,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        $sharedPrize = Prize::query()->create([
            'title' => 'Shared Prize',
            'description' => 'Ranks 2-3 reward.',
            'quantity' => 2,
            'default_rank_from' => 2,
            'default_rank_to' => 3,
            'is_active' => true,
        ]);

        $service = app(PrizeService::class);

        $this->assertSame($firstPrize->id, $service->findConfiguredPrizeByRank(1)?->id);
        $this->assertSame($sharedPrize->id, $service->findConfiguredPrizeByRank(2)?->id);
        $this->assertSame($sharedPrize->id, $service->findConfiguredPrizeByRank(3)?->id);
        $this->assertNull($service->findConfiguredPrizeByRank(4));
    }

    public function test_it_reports_duplicate_assignment_as_a_skip_reason(): void
    {
        $user = User::factory()->create();

        $prize = Prize::query()->create([
            'title' => 'Duplicate Prize',
            'description' => 'Already assigned reward.',
            'quantity' => 5,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        $user->userPrizes()->create([
            'prize_id' => $prize->id,
            'rank_at_assignment' => 1,
            'assigned_manually' => false,
            'assigned_by' => null,
            'assigned_at' => now(),
            'status' => \App\Enums\UserPrizeStatus::PENDING,
        ]);

        $this->assertSame(
            'duplicate_assignment',
            app(PrizeService::class)->getAssignmentSkipReason($user, 1),
        );
    }
}
