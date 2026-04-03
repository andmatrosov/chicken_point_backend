<?php

namespace Tests\Feature\Prize;

use App\Actions\AssignPrizeManuallyAction;
use App\Enums\UserPrizeStatus;
use App\Exceptions\BusinessException;
use App\Models\Prize;
use App\Models\User;
use App\Services\PrizeAutoAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrizeAssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_assignment_workflow_creates_prize_assignments_for_current_leaderboard(): void
    {
        config()->set('game.leaderboard.size', 2);
        config()->set('game.prizes.use_remaining_stock', true);

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $first = User::factory()->create([
            'best_score' => 1000,
        ]);

        $second = User::factory()->create([
            'best_score' => 900,
        ]);

        Prize::query()->create([
            'title' => 'Rank One Prize',
            'description' => 'First place reward.',
            'quantity' => 1,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        Prize::query()->create([
            'title' => 'Rank Two Prize',
            'description' => 'Second place reward.',
            'quantity' => 1,
            'default_rank_from' => 2,
            'default_rank_to' => 2,
            'is_active' => true,
        ]);

        $result = app(PrizeAutoAssignmentService::class)->assignCurrentLeaderboardPrizes($admin);

        $this->assertSame('assign', $result['mode']);
        $this->assertSame(2, $result['assigned_count']);
        $this->assertSame(0, $result['skipped_count']);
        $this->assertDatabaseHas('user_prizes', [
            'user_id' => $first->id,
            'rank_at_assignment' => 1,
            'status' => UserPrizeStatus::PENDING->value,
        ]);
        $this->assertDatabaseHas('user_prizes', [
            'user_id' => $second->id,
            'rank_at_assignment' => 2,
            'status' => UserPrizeStatus::PENDING->value,
        ]);
    }

    public function test_manual_assignment_action_creates_a_user_prize_record(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create();

        $prize = Prize::query()->create([
            'title' => 'Manual Prize',
            'description' => 'Assigned manually.',
            'quantity' => 2,
            'default_rank_from' => null,
            'default_rank_to' => null,
            'is_active' => true,
        ]);

        $userPrize = app(AssignPrizeManuallyAction::class)($user, $prize, $admin, 4);

        $this->assertSame($user->id, $userPrize->user_id);
        $this->assertSame($prize->id, $userPrize->prize_id);
        $this->assertSame(4, $userPrize->rank_at_assignment);
        $this->assertTrue($userPrize->assigned_manually);
        $this->assertSame($admin->id, $userPrize->assigned_by);
        $this->assertDatabaseHas('user_prizes', [
            'id' => $userPrize->id,
            'status' => UserPrizeStatus::PENDING->value,
        ]);
    }

    public function test_out_of_stock_assignment_is_blocked(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create();

        $prize = Prize::query()->create([
            'title' => 'Out of Stock Prize',
            'description' => 'Unavailable reward.',
            'quantity' => 0,
            'default_rank_from' => null,
            'default_rank_to' => null,
            'is_active' => true,
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('This prize is out of stock.');

        app(AssignPrizeManuallyAction::class)($user, $prize, $admin, null);
    }
}
