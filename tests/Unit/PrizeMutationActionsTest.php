<?php

namespace Tests\Unit;

use App\Actions\CancelUserPrizeAction;
use App\Actions\DeletePrizeAction;
use App\Actions\MarkUserPrizeIssuedAction;
use App\Enums\UserPrizeStatus;
use App\Exceptions\BusinessException;
use App\Models\AdminActionLog;
use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrizeMutationActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_canceling_an_assignment_marks_it_as_canceled(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        [$admin, $prize, $userPrize] = $this->makePendingAssignment(quantity: 0);

        $canceledPrize = app(CancelUserPrizeAction::class)($userPrize, $admin);

        $this->assertSame(UserPrizeStatus::CANCELED, $canceledPrize->status);
        $this->assertDatabaseHas('user_prizes', [
            'id' => $userPrize->id,
            'status' => UserPrizeStatus::CANCELED->value,
        ]);
    }

    public function test_canceling_restores_stock_when_remaining_stock_mode_is_enabled(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        [$admin, $prize, $userPrize] = $this->makePendingAssignment(quantity: 0);

        app(CancelUserPrizeAction::class)($userPrize, $admin);

        $this->assertSame(1, $prize->fresh()->quantity);
    }

    public function test_canceling_an_already_canceled_assignment_is_rejected_without_restoring_stock_twice(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        [$admin, $prize, $userPrize] = $this->makePendingAssignment(quantity: 0);

        $cancelUserPrizeAction = app(CancelUserPrizeAction::class);

        $cancelUserPrizeAction($userPrize, $admin);

        try {
            $cancelUserPrizeAction($userPrize->fresh(), $admin);
            $this->fail('Expected invalid transition to be rejected.');
        } catch (BusinessException $exception) {
            $this->assertSame('Only pending prize assignments can be canceled.', $exception->getMessage());
        }

        $this->assertSame(1, $prize->fresh()->quantity);
        $this->assertSame(1, AdminActionLog::query()->where('action', 'cancel_prize_assignment')->count());
    }

    public function test_non_admin_cannot_cancel_assignment_in_domain_layer(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        [$admin, $prize, $userPrize] = $this->makePendingAssignment(quantity: 0);

        $nonAdmin = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Only admins can perform this action.');

        app(CancelUserPrizeAction::class)($userPrize, $nonAdmin);
    }

    public function test_deleting_a_prize_deletes_related_assignments_and_the_prize_itself(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $prize = Prize::query()->create([
            'title' => 'Delete Me',
            'description' => 'Temporary prize.',
            'quantity' => 4,
            'default_rank_from' => 1,
            'default_rank_to' => 2,
            'is_active' => true,
        ]);

        $firstWinner = User::factory()->create();
        $secondWinner = User::factory()->create();

        $firstAssignment = UserPrize::query()->create([
            'user_id' => $firstWinner->id,
            'prize_id' => $prize->id,
            'rank_at_assignment' => 1,
            'assigned_manually' => false,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'status' => UserPrizeStatus::PENDING,
        ]);

        $secondAssignment = UserPrize::query()->create([
            'user_id' => $secondWinner->id,
            'prize_id' => $prize->id,
            'rank_at_assignment' => 2,
            'assigned_manually' => true,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'status' => UserPrizeStatus::ISSUED,
        ]);

        app(DeletePrizeAction::class)($prize, $admin);

        $this->assertDatabaseMissing('prizes', [
            'id' => $prize->id,
        ]);

        $this->assertDatabaseMissing('user_prizes', [
            'id' => $firstAssignment->id,
        ]);

        $this->assertDatabaseMissing('user_prizes', [
            'id' => $secondAssignment->id,
        ]);
    }

    public function test_non_admin_cannot_delete_prize_in_domain_layer(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $prize = Prize::query()->create([
            'title' => 'Restricted Deletion',
            'description' => 'Admin only.',
            'quantity' => 1,
            'default_rank_from' => null,
            'default_rank_to' => null,
            'is_active' => true,
        ]);

        UserPrize::query()->create([
            'user_id' => User::factory()->create()->id,
            'prize_id' => $prize->id,
            'rank_at_assignment' => null,
            'assigned_manually' => true,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'status' => UserPrizeStatus::PENDING,
        ]);

        $nonAdmin = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Only admins can perform this action.');

        app(DeletePrizeAction::class)($prize, $nonAdmin);
    }

    public function test_canceling_assignment_writes_a_structured_admin_action_log(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        [$admin, $prize, $userPrize] = $this->makePendingAssignment(quantity: 0);

        app(CancelUserPrizeAction::class)($userPrize, $admin);

        $log = AdminActionLog::query()->where('action', 'cancel_prize_assignment')->firstOrFail();

        $this->assertSame($admin->id, $log->admin_user_id);
        $this->assertSame('prize_assignment', $log->entity_type);
        $this->assertSame($userPrize->id, $log->entity_id);
        $this->assertSame($userPrize->id, $log->payload['user_prize_id']);
        $this->assertSame($prize->id, $log->payload['prize_id']);
        $this->assertSame($userPrize->user_id, $log->payload['user_id']);
        $this->assertSame(UserPrizeStatus::PENDING->value, $log->payload['previous_status']);
        $this->assertSame(UserPrizeStatus::CANCELED->value, $log->payload['new_status']);
        $this->assertSame(1, $log->payload['stock_delta']);
    }

    public function test_canceling_an_issued_assignment_is_rejected_without_stock_change(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        [$admin, $prize, $userPrize] = $this->makePendingAssignment(quantity: 0);

        app(MarkUserPrizeIssuedAction::class)($userPrize, $admin);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Only pending prize assignments can be canceled.');

        try {
            app(CancelUserPrizeAction::class)($userPrize->fresh(), $admin);
        } finally {
            $this->assertSame(0, $prize->fresh()->quantity);
            $this->assertSame(UserPrizeStatus::ISSUED, $userPrize->fresh()->status);
        }
    }

    public function test_marking_an_assignment_as_issued_updates_status_without_changing_stock(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        [$admin, $prize, $userPrize] = $this->makePendingAssignment(quantity: 0);

        $issuedPrize = app(MarkUserPrizeIssuedAction::class)($userPrize, $admin);

        $this->assertSame(UserPrizeStatus::ISSUED, $issuedPrize->status);
        $this->assertSame(0, $prize->fresh()->quantity);
        $this->assertDatabaseHas('user_prizes', [
            'id' => $userPrize->id,
            'status' => UserPrizeStatus::ISSUED->value,
        ]);
    }

    public function test_marking_an_issued_assignment_as_issued_again_is_rejected_without_corrupting_state(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        [$admin, $prize, $userPrize] = $this->makePendingAssignment(quantity: 0);

        $markUserPrizeIssuedAction = app(MarkUserPrizeIssuedAction::class);

        $markUserPrizeIssuedAction($userPrize, $admin);

        try {
            $markUserPrizeIssuedAction($userPrize->fresh(), $admin);
            $this->fail('Expected invalid transition to be rejected.');
        } catch (BusinessException $exception) {
            $this->assertSame('Only pending prize assignments can be marked as issued.', $exception->getMessage());
        }

        $this->assertSame(0, $prize->fresh()->quantity);
        $this->assertSame(UserPrizeStatus::ISSUED, $userPrize->fresh()->status);
        $this->assertSame(1, AdminActionLog::query()->where('action', 'mark_prize_assignment_issued')->count());
    }

    public function test_marking_a_canceled_assignment_as_issued_is_rejected(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        [$admin, $prize, $userPrize] = $this->makePendingAssignment(quantity: 0);

        app(CancelUserPrizeAction::class)($userPrize, $admin);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Only pending prize assignments can be marked as issued.');

        try {
            app(MarkUserPrizeIssuedAction::class)($userPrize->fresh(), $admin);
        } finally {
            $this->assertSame(1, $prize->fresh()->quantity);
            $this->assertSame(UserPrizeStatus::CANCELED, $userPrize->fresh()->status);
        }
    }

    public function test_marking_assignment_as_issued_writes_a_structured_admin_action_log(): void
    {
        config()->set('game.prizes.use_remaining_stock', true);

        [$admin, $prize, $userPrize] = $this->makePendingAssignment(quantity: 0);

        app(MarkUserPrizeIssuedAction::class)($userPrize, $admin);

        $log = AdminActionLog::query()->where('action', 'mark_prize_assignment_issued')->firstOrFail();

        $this->assertSame($admin->id, $log->admin_user_id);
        $this->assertSame('prize_assignment', $log->entity_type);
        $this->assertSame($userPrize->id, $log->entity_id);
        $this->assertSame($userPrize->id, $log->payload['user_prize_id']);
        $this->assertSame($prize->id, $log->payload['prize_id']);
        $this->assertSame($userPrize->user_id, $log->payload['user_id']);
        $this->assertSame(UserPrizeStatus::PENDING->value, $log->payload['previous_status']);
        $this->assertSame(UserPrizeStatus::ISSUED->value, $log->payload['new_status']);
        $this->assertSame(0, $log->payload['stock_delta']);
    }

    public function test_deleting_prize_writes_a_structured_admin_action_log(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $prize = Prize::query()->create([
            'title' => 'Logged Prize',
            'description' => 'Logging check.',
            'quantity' => 2,
            'default_rank_from' => null,
            'default_rank_to' => null,
            'is_active' => true,
        ]);

        $winner = User::factory()->create();

        $assignment = UserPrize::query()->create([
            'user_id' => $winner->id,
            'prize_id' => $prize->id,
            'rank_at_assignment' => null,
            'assigned_manually' => true,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'status' => UserPrizeStatus::PENDING,
        ]);

        app(DeletePrizeAction::class)($prize, $admin);

        $log = AdminActionLog::query()->where('action', 'delete_prize')->firstOrFail();

        $this->assertSame($admin->id, $log->admin_user_id);
        $this->assertSame('prize', $log->entity_type);
        $this->assertSame($prize->id, $log->entity_id);
        $this->assertSame($prize->id, $log->payload['prize_id']);
        $this->assertSame('Logged Prize', $log->payload['prize_title']);
        $this->assertSame(1, $log->payload['number_of_deleted_assignments']);
        $this->assertSame([$winner->id], $log->payload['affected_user_ids']);
        $this->assertSame([$assignment->id], $log->payload['deleted_assignment_ids']);
        $this->assertTrue($log->payload['remaining_stock_model']);
        $this->assertSame(2, $log->payload['quantity_before_delete']);
    }

    /**
     * @return array{0: User, 1: Prize, 2: UserPrize}
     */
    protected function makePendingAssignment(int $quantity): array
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $winner = User::factory()->create();

        $prize = Prize::query()->create([
            'title' => 'Cancelable Prize',
            'description' => 'Test prize.',
            'quantity' => $quantity,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        $userPrize = UserPrize::query()->create([
            'user_id' => $winner->id,
            'prize_id' => $prize->id,
            'rank_at_assignment' => 1,
            'assigned_manually' => false,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'status' => UserPrizeStatus::PENDING,
        ]);

        return [$admin, $prize, $userPrize];
    }
}
