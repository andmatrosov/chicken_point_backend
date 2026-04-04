<?php

namespace Tests\Feature\Admin;

use App\Enums\UserPrizeStatus;
use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_the_filament_panel(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();

        foreach ([
            '/admin/users',
            '/admin/skins',
            '/admin/prizes',
            '/admin/user-prizes',
            '/admin/game-scores',
            '/admin/game-sessions',
            '/admin/leaderboard',
            '/admin/admin-action-logs',
        ] as $path) {
            $this->actingAs($admin)
                ->get($path)
                ->assertOk();
        }
    }

    public function test_non_admin_cannot_access_the_filament_panel(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();

        foreach ([
            '/admin/users',
            '/admin/skins',
            '/admin/prizes',
            '/admin/user-prizes',
            '/admin/game-scores',
            '/admin/game-sessions',
            '/admin/leaderboard',
            '/admin/admin-action-logs',
        ] as $path) {
            $this->actingAs($user)
                ->get($path)
                ->assertForbidden();
        }
    }

    public function test_user_prize_edit_page_is_not_available_even_for_admins(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $winner = User::factory()->create();

        $prize = Prize::query()->create([
            'title' => 'Read Only Prize',
            'description' => 'Transition-safe admin flow.',
            'quantity' => 1,
            'default_rank_from' => null,
            'default_rank_to' => null,
            'is_active' => true,
        ]);

        $userPrize = UserPrize::query()->create([
            'user_id' => $winner->id,
            'prize_id' => $prize->id,
            'rank_at_assignment' => null,
            'assigned_manually' => true,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'status' => UserPrizeStatus::PENDING,
        ]);

        $this->actingAs($admin)
            ->get("/admin/user-prizes/{$userPrize->id}/edit")
            ->assertNotFound();
    }
}
