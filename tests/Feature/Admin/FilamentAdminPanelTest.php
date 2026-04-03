<?php

namespace Tests\Feature\Admin;

use App\Models\User;
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
}
