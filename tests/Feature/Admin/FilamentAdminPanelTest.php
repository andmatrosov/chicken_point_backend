<?php

namespace Tests\Feature\Admin;

use App\Enums\GameSessionStatus;
use App\Enums\UserPrizeStatus;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\GameScore;
use App\Models\GameSession;
use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use App\Models\UserSuspiciousEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_admin_can_see_related_profiles_by_registration_ip_on_user_view_page(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'primary-ip@example.com',
            'registration_ip' => '198.51.100.42',
            'country_name' => 'Georgia',
        ]);

        $relatedOne = User::factory()->create([
            'email' => 'secondary-ip@example.com',
            'registration_ip' => '198.51.100.42',
            'country_name' => 'Georgia',
            'best_score' => 120,
            'has_suspicious_game_results' => false,
        ]);

        $relatedTwo = User::factory()->create([
            'email' => 'flagged-ip@example.com',
            'registration_ip' => '198.51.100.42',
            'country_name' => 'Brazil',
            'best_score' => 450,
            'has_suspicious_game_results' => true,
        ]);

        User::factory()->create([
            'email' => 'other-ip@example.com',
            'registration_ip' => '203.0.113.77',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/users/{$user->id}");

        $response
            ->assertOk()
            ->assertSeeText('Связанные профили')
            ->assertDontSeeText('Связанные профили отсутствуют');
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

    public function test_admin_can_view_game_session_with_nested_metadata_and_no_expiration(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $player = User::factory()->create([
            'email' => 'session-player@example.com',
        ]);

        $gameSession = GameSession::query()->create([
            'user_id' => $player->id,
            'token' => 'nested-metadata-session',
            'status' => GameSessionStatus::SUBMITTED,
            'issued_at' => now()->subHours(3),
            'expires_at' => null,
            'submitted_at' => now()->subHours(2),
            'metadata' => [
                'device_id' => 'ios-device-1',
                'submission' => [
                    'duration' => 120,
                    'platform' => 'ios',
                ],
            ],
        ]);

        $this->actingAs($admin)
            ->get("/admin/game-sessions/{$gameSession->id}")
            ->assertOk()
            ->assertSeeText('Игровые сессии')
            ->assertSeeText('Без ограничения по времени')
            ->assertSeeText('nested-metadata-session')
            ->assertSeeText('"duration":120');
    }

    public function test_admin_can_see_game_results_relation_with_suspicious_context(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create();

        $session = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'view-user-score-session',
            'status' => GameSessionStatus::SUBMITTED,
            'issued_at' => now()->subSeconds(20),
            'submitted_at' => now(),
            'expires_at' => null,
            'metadata' => [
                'submission' => [
                    'duration' => 120,
                    'device_id' => 'ios-1',
                    'platform' => 'ios',
                    'app_version' => '1.2.3',
                ],
            ],
        ]);

        $score = GameScore::query()->create([
            'user_id' => $user->id,
            'score' => 80,
            'coins_collected' => 5,
            'session_token' => $session->token,
            'is_processed' => true,
        ]);

        UserSuspiciousEvent::query()->create([
            'user_id' => $user->id,
            'game_score_id' => $score->id,
            'reason' => 'high_score_velocity',
            'points' => 1,
            'signals' => [
                ['reason' => 'high_score_velocity', 'points' => 1, 'level' => 'soft', 'counts_for_points' => true, 'category' => 'cheat'],
                ['reason' => 'duration_mismatch', 'points' => 0, 'level' => 'soft', 'counts_for_points' => false, 'category' => 'timing'],
            ],
            'context' => [
                'server_duration_runtime_style' => 20,
                'client_duration' => 120,
            ],
        ]);

        $this->actingAs($admin)
            ->get('/admin/users/'.$user->id)
            ->assertOk()
            ->assertSeeText('Игровые результаты')
            ->assertSeeText('Слишком высокая скорость набора очков')
            ->assertSeeText('Несоответствие времени сессии')
            ->assertSeeText('Client duration')
            ->assertSeeText('Server score/sec (submitted)')
            ->assertSeeText('Server duration (score created)')
            ->assertSeeText('Надежно')
            ->assertSeeText('soft');
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
                'has_suspicious_game_results' => false,
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'player@example.com',
        ]);
    }

    public function test_admin_can_see_suspicious_user_flag_in_users_list(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        User::factory()->create([
            'email' => 'flagged-player@example.com',
            'has_suspicious_game_results' => true,
            'suspicious_game_result_points' => 3,
            'suspicious_game_results_reason' => 'score_velocity_exceeded',
            'suspicious_game_results_flagged_at' => now(),
        ]);

        User::factory()->create([
            'email' => 'points-player@example.com',
            'has_suspicious_game_results' => false,
            'suspicious_game_result_points' => 2,
        ]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSeeText('Античит')
            ->assertSeeText('Подозрительный')
            ->assertSeeText('Есть points')
            ->assertSeeText('Points');
    }

    public function test_admin_can_toggle_suspicious_user_flag_manually(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'has_suspicious_game_results' => false,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'email' => $user->email,
                'password' => null,
                'coins' => $user->coins,
                'best_score' => $user->best_score,
                'has_suspicious_game_results' => true,
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'has_suspicious_game_results' => true,
            'suspicious_game_results_reason' => 'manual_admin_flag',
        ]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'email' => $user->email,
                'password' => null,
                'coins' => $user->coins,
                'best_score' => $user->best_score,
                'has_suspicious_game_results' => false,
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'has_suspicious_game_results' => false,
            'suspicious_game_results_reason' => null,
        ]);
    }

    public function test_admin_can_reset_suspicion_points_without_clearing_flag(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'has_suspicious_game_results' => true,
            'suspicious_game_result_points' => 4,
            'suspicious_game_results_reason' => 'adaptive_score_limit_exceeded',
            'suspicious_game_results_flagged_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction('resetSuspicionPoints');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'suspicious_game_result_points' => 0,
            'has_suspicious_game_results' => true,
        ]);

        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'reset_user_suspicion_points',
            'entity_type' => 'user',
            'entity_id' => $user->id,
        ]);
    }

    public function test_admin_can_edit_suspicion_points_without_changing_permanent_flag(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'has_suspicious_game_results' => true,
            'suspicious_game_result_points' => 4,
            'suspicious_game_results_reason' => 'adaptive_score_limit_exceeded',
            'suspicious_game_results_flagged_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'email' => $user->email,
                'password' => null,
                'coins' => $user->coins,
                'best_score' => $user->best_score,
                'suspicious_game_result_points' => 1,
                'has_suspicious_game_results' => true,
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'suspicious_game_result_points' => 1,
            'has_suspicious_game_results' => true,
        ]);

        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'edit_user_suspicion_points',
            'entity_type' => 'user',
            'entity_id' => $user->id,
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
                'has_suspicious_game_results' => false,
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
                'has_suspicious_game_results' => false,
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
                'has_suspicious_game_results' => false,
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
