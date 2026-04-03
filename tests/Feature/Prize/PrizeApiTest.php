<?php

namespace Tests\Feature\Prize;

use App\Enums\UserPrizeStatus;
use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrizeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_prizes_returns_only_authenticated_users_prizes(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $prize = Prize::query()->create([
            'title' => 'Top Prize',
            'description' => 'Awarded to the best players.',
            'quantity' => 5,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        $otherPrize = Prize::query()->create([
            'title' => 'Hidden Prize',
            'description' => 'Should not leak.',
            'quantity' => 3,
            'default_rank_from' => 2,
            'default_rank_to' => 3,
            'is_active' => true,
        ]);

        UserPrize::query()->create([
            'user_id' => $user->id,
            'prize_id' => $prize->id,
            'rank_at_assignment' => 1,
            'assigned_manually' => false,
            'assigned_by' => null,
            'assigned_at' => now(),
            'status' => UserPrizeStatus::PENDING,
        ]);

        UserPrize::query()->create([
            'user_id' => $otherUser->id,
            'prize_id' => $otherPrize->id,
            'rank_at_assignment' => 2,
            'assigned_manually' => true,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
            'status' => UserPrizeStatus::ISSUED,
        ]);

        $plainTextToken = $user->createToken('mobile-client')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/prizes/my');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.prize.title', 'Top Prize')
            ->assertJsonPath('data.0.status', UserPrizeStatus::PENDING->value)
            ->assertJsonMissingPath('data.0.user_id')
            ->assertJsonMissingPath('data.0.assigned_by');
    }
}
