<?php

namespace Tests\Feature\Admin;

use App\Enums\UserPrizeStatus;
use App\Models\GameScore;
use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Filament\Resources\Users\Pages\EditUser;

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
            '/admin/mvp-settings',
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
            '/admin/mvp-settings',
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

    public function test_admin_can_see_current_rank_ip_and_country_on_user_view_page(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        User::factory()->create([
            'email' => 'top@example.com',
            'best_score' => 1200,
        ]);

        $user = User::factory()->create([
            'email' => 'geo-ranked@example.com',
            'best_score' => 900,
            'registration_ip' => '203.0.113.10',
            'country_name' => 'Georgia',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/users/{$user->id}");

        $response
            ->assertOk()
            ->assertSeeText('Текущий ранг')
            ->assertSeeText('2')
            ->assertSeeText('IP')
            ->assertSeeText('203.0.113.10')
            ->assertSeeText('Страна')
            ->assertSeeText('Georgia');
    }

    public function test_admin_can_see_collected_coins_on_game_scores_list(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $player = User::factory()->create([
            'email' => 'score-player@example.com',
        ]);

        GameScore::query()->create([
            'user_id' => $player->id,
            'score' => 900,
            'coins_collected' => 17,
            'session_token' => 'coins-visible-session',
            'is_processed' => true,
        ]);

        GameScore::query()->create([
            'user_id' => $player->id,
            'score' => 700,
            'session_token' => 'coins-default-session',
            'is_processed' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/game-scores');

        $response
            ->assertOk()
            ->assertSeeText('Собрано монет')
            ->assertSeeText('17')
            ->assertSeeText('0');
    }

    public function test_filament_user_edit_normalizes_email_before_validation_and_save(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'player@example.com',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'email' => '  PLAYER@Example.com ',
                'password' => null,
                'coins' => $user->coins,
                'best_score' => $user->best_score,
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'player@example.com',
        ]);
    }

    public function test_admin_cannot_remove_their_own_admin_rights(): void
    {
        $admin = User::factory()->create([
            'email' => 'self-admin@example.com',
            'is_admin' => true,
        ]);

        User::factory()->create([
            'email' => 'other-admin@example.com',
            'is_admin' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm([
                'email' => $admin->email,
                'password' => null,
                'coins' => $admin->coins,
                'best_score' => $admin->best_score,
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasErrors([
                'is_admin' => 'You cannot remove your own admin access.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_admin' => true,
        ]);
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->create([
            'email' => 'last-admin@example.com',
            'is_admin' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm([
                'email' => $admin->email,
                'password' => null,
                'coins' => $admin->coins,
                'best_score' => $admin->best_score,
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasErrors([
                'is_admin' => 'At least one admin must remain in the system.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_demote_another_admin_when_more_than_one_admin_exists(): void
    {
        $actingAdmin = User::factory()->create([
            'email' => 'acting-admin@example.com',
            'is_admin' => true,
        ]);

        $demotedAdmin = User::factory()->create([
            'email' => 'demoted-admin@example.com',
            'is_admin' => true,
        ]);

        $this->actingAs($actingAdmin);

        Livewire::test(EditUser::class, ['record' => $demotedAdmin->getRouteKey()])
            ->fillForm([
                'email' => $demotedAdmin->email,
                'password' => null,
                'coins' => $demotedAdmin->coins,
                'best_score' => $demotedAdmin->best_score,
                'is_admin' => false,
            ])
            ->call('save')
            ->assertStatus(200)
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $actingAdmin->id,
            'is_admin' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $demotedAdmin->id,
            'is_admin' => false,
        ]);
    }
}
