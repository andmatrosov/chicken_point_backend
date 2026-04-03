<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AdminActionLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActionLogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_manual_user_balance_edits_with_structured_payload(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'coins' => 150,
        ]);

        app(AdminActionLogService::class)->logUserBalanceEdit($admin, $user, 150, 90);

        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'edit_user_balance',
            'entity_type' => 'user',
            'entity_id' => $user->id,
        ]);

        $payload = \App\Models\AdminActionLog::query()->where('action', 'edit_user_balance')->firstOrFail()->payload;

        $this->assertSame($user->id, $payload['user_id']);
        $this->assertSame(150, $payload['changes']['coins']['old']);
        $this->assertSame(90, $payload['changes']['coins']['new']);
        $this->assertSame(-60, $payload['changes']['coins']['delta']);
    }

    public function test_it_logs_admin_user_data_changes_without_exposing_password_values(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'before@example.com',
            'best_score' => 100,
            'is_admin' => false,
        ]);

        app(AdminActionLogService::class)->logUserDataUpdate(
            $admin,
            $user,
            [
                'email' => [
                    'old' => 'before@example.com',
                    'new' => 'after@example.com',
                ],
                'best_score' => [
                    'old' => 100,
                    'new' => 250,
                ],
                'is_admin' => [
                    'old' => false,
                    'new' => true,
                ],
            ],
            true,
        );

        $payload = \App\Models\AdminActionLog::query()->where('action', 'update_user_admin_data')->firstOrFail()->payload;

        $this->assertSame($user->id, $payload['user_id']);
        $this->assertSame('before@example.com', $payload['changes']['email']['old']);
        $this->assertSame('after@example.com', $payload['changes']['email']['new']);
        $this->assertSame(100, $payload['changes']['best_score']['old']);
        $this->assertSame(250, $payload['changes']['best_score']['new']);
        $this->assertSame(false, $payload['changes']['is_admin']['old']);
        $this->assertSame(true, $payload['changes']['is_admin']['new']);
        $this->assertSame(['changed' => true], $payload['changes']['password']);
    }
}
