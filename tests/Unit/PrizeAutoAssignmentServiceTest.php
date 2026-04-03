<?php

namespace Tests\Unit;

use App\Enums\UserPrizeStatus;
use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use App\Services\PrizeAutoAssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrizeAutoAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_returns_structured_warnings_and_does_not_write_assignments(): void
    {
        config()->set('game.leaderboard.size', 4);
        config()->set('game.prizes.use_remaining_stock', true);

        $first = User::factory()->create(['best_score' => 1000]);
        $second = User::factory()->create(['best_score' => 900]);
        $third = User::factory()->create(['best_score' => 800]);
        $fourth = User::factory()->create(['best_score' => 700]);
        $admin = User::factory()->create(['is_admin' => true]);

        Prize::query()->create([
            'title' => 'Gold Prize',
            'description' => 'Rank 1 reward.',
            'quantity' => 1,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        Prize::query()->create([
            'title' => 'Shared Prize',
            'description' => 'Ranks 2-3 reward.',
            'quantity' => 1,
            'default_rank_from' => 2,
            'default_rank_to' => 3,
            'is_active' => true,
        ]);

        Prize::query()->create([
            'title' => 'Inactive Prize',
            'description' => 'Rank 4 reward.',
            'quantity' => 2,
            'default_rank_from' => 4,
            'default_rank_to' => 4,
            'is_active' => false,
        ]);

        $result = app(PrizeAutoAssignmentService::class)->previewCurrentLeaderboardAssignments($admin);

        $this->assertSame('preview', $result['mode']);
        $this->assertSame(4, $result['processed_count']);
        $this->assertSame(2, $result['ready_count']);
        $this->assertSame(0, $result['assigned_count']);
        $this->assertSame(2, $result['skipped_count']);
        $this->assertSame('ready', $result['entries'][0]['status']);
        $this->assertSame($first->id, $result['entries'][0]['user_id']);
        $this->assertSame('ready', $result['entries'][1]['status']);
        $this->assertSame($second->id, $result['entries'][1]['user_id']);
        $this->assertSame('warning', $result['entries'][2]['status']);
        $this->assertSame('out_of_stock', $result['entries'][2]['reason']);
        $this->assertSame($third->id, $result['entries'][2]['user_id']);
        $this->assertSame('warning', $result['entries'][3]['status']);
        $this->assertSame('prize_inactive', $result['entries'][3]['reason']);
        $this->assertSame($fourth->id, $result['entries'][3]['user_id']);
        $this->assertDatabaseCount('user_prizes', 0);
        $this->assertDatabaseCount('admin_action_logs', 0);
    }

    public function test_assign_current_leaderboard_prizes_creates_assignments_and_logs_run(): void
    {
        config()->set('game.leaderboard.size', 3);
        config()->set('game.prizes.use_remaining_stock', true);

        $admin = User::factory()->create(['is_admin' => true]);
        $first = User::factory()->create(['best_score' => 1000]);
        $second = User::factory()->create(['best_score' => 900]);
        $third = User::factory()->create(['best_score' => 800]);

        $rankOnePrize = Prize::query()->create([
            'title' => 'Rank 1 Prize',
            'description' => 'Rank 1 reward.',
            'quantity' => 1,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        $sharedPrize = Prize::query()->create([
            'title' => 'Shared Prize',
            'description' => 'Ranks 2-3 reward.',
            'quantity' => 1,
            'default_rank_from' => 2,
            'default_rank_to' => 3,
            'is_active' => true,
        ]);

        $result = app(PrizeAutoAssignmentService::class)->assignCurrentLeaderboardPrizes($admin);

        $this->assertSame('assign', $result['mode']);
        $this->assertSame(3, $result['processed_count']);
        $this->assertSame(0, $result['ready_count']);
        $this->assertSame(2, $result['assigned_count']);
        $this->assertSame(1, $result['skipped_count']);
        $this->assertSame('assigned', $result['entries'][0]['status']);
        $this->assertSame($first->id, $result['entries'][0]['user_id']);
        $this->assertSame('assigned', $result['entries'][1]['status']);
        $this->assertSame($second->id, $result['entries'][1]['user_id']);
        $this->assertSame('skipped', $result['entries'][2]['status']);
        $this->assertSame('out_of_stock', $result['entries'][2]['reason']);
        $this->assertSame($third->id, $result['entries'][2]['user_id']);

        $this->assertDatabaseHas('user_prizes', [
            'user_id' => $first->id,
            'prize_id' => $rankOnePrize->id,
            'status' => UserPrizeStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('user_prizes', [
            'user_id' => $second->id,
            'prize_id' => $sharedPrize->id,
            'status' => UserPrizeStatus::PENDING->value,
        ]);

        $this->assertSame(0, $rankOnePrize->fresh()->quantity);
        $this->assertSame(0, $sharedPrize->fresh()->quantity);
        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'auto_assign_prizes',
            'entity_type' => 'prize_assignment',
            'entity_id' => 0,
        ]);
    }

    public function test_assign_current_leaderboard_prizes_skips_duplicate_assignments(): void
    {
        config()->set('game.leaderboard.size', 2);
        config()->set('game.prizes.use_remaining_stock', true);

        $first = User::factory()->create(['best_score' => 1000]);
        $second = User::factory()->create(['best_score' => 900]);

        $rankPrize = Prize::query()->create([
            'title' => 'Rank Prize',
            'description' => 'Ranks 1-2 reward.',
            'quantity' => 3,
            'default_rank_from' => 1,
            'default_rank_to' => 2,
            'is_active' => true,
        ]);

        UserPrize::query()->create([
            'user_id' => $first->id,
            'prize_id' => $rankPrize->id,
            'rank_at_assignment' => 1,
            'assigned_manually' => false,
            'assigned_by' => null,
            'assigned_at' => now(),
            'status' => UserPrizeStatus::PENDING,
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        $result = app(PrizeAutoAssignmentService::class)->assignCurrentLeaderboardPrizes($admin);

        $this->assertSame(1, $result['assigned_count']);
        $this->assertSame(1, $result['skipped_count']);
        $this->assertSame('skipped', $result['entries'][0]['status']);
        $this->assertSame('duplicate_assignment', $result['entries'][0]['reason']);
        $this->assertSame('assigned', $result['entries'][1]['status']);
        $this->assertSame(2, UserPrize::query()->count());
        $this->assertSame(2, $rankPrize->fresh()->quantity);
        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'auto_assign_prizes',
            'entity_type' => 'prize_assignment',
            'entity_id' => 0,
        ]);
    }

    public function test_non_admin_cannot_run_auto_assignment_workflow(): void
    {
        $nonAdmin = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Only admins can perform this action.');

        app(PrizeAutoAssignmentService::class)->assignCurrentLeaderboardPrizes($nonAdmin);
    }
}
