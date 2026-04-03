<?php

namespace Tests\Unit;

use App\Actions\AssignPrizeByRankAction;
use App\Actions\AssignPrizeManuallyAction;
use App\Exceptions\BusinessException;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrizeAssignmentActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_prize_by_rank_creates_assignment_and_decrements_stock(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        $user = User::factory()->create();
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $prize = Prize::query()->create([
            'title' => 'Rank 1 Prize',
            'description' => 'Top player reward.',
            'quantity' => 2,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        $userPrize = app(AssignPrizeByRankAction::class)($user, 1, $admin);

        $this->assertSame($user->id, $userPrize->user_id);
        $this->assertSame($prize->id, $userPrize->prize_id);
        $this->assertSame(1, $userPrize->rank_at_assignment);
        $this->assertFalse($userPrize->assigned_manually);
        $this->assertSame($admin->id, $userPrize->assigned_by);
        $this->assertSame(1, $prize->fresh()->quantity);
        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'assign_prize_by_rank',
            'entity_type' => 'prize_assignment',
            'entity_id' => $userPrize->id,
        ]);
    }

    public function test_assign_prize_manually_prevents_duplicate_active_assignments(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        $user = User::factory()->create();
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $prize = Prize::query()->create([
            'title' => 'Manual Prize',
            'description' => 'Manual reward.',
            'quantity' => 3,
            'default_rank_from' => null,
            'default_rank_to' => null,
            'is_active' => true,
        ]);

        $assignPrizeManuallyAction = app(AssignPrizeManuallyAction::class);

        $assignedPrize = $assignPrizeManuallyAction($user, $prize, $admin, 5);

        $this->assertSame($admin->id, $assignedPrize->assigned_by);
        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'assign_prize_manually',
            'entity_type' => 'prize_assignment',
            'entity_id' => $assignedPrize->id,
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('This prize has already been assigned to the user.');

        $assignPrizeManuallyAction($user, $prize, $admin, 5);
    }

    public function test_assign_prize_by_rank_rejects_empty_stock(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        $user = User::factory()->create();
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Prize::query()->create([
            'title' => 'No Stock Prize',
            'description' => 'Unavailable reward.',
            'quantity' => 0,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('This prize is out of stock.');

        app(AssignPrizeByRankAction::class)($user, 1, $admin);
    }

    public function test_non_admin_cannot_assign_prizes(): void
    {
        $user = User::factory()->create();
        $nonAdmin = User::factory()->create([
            'is_admin' => false,
        ]);

        $prize = Prize::query()->create([
            'title' => 'Restricted Prize',
            'description' => 'Admin only.',
            'quantity' => 1,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Only admins can perform this action.');

        app(AssignPrizeManuallyAction::class)($user, $prize, $nonAdmin, 1);
    }
}
