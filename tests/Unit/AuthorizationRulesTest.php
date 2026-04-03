<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gates_allow_admin_and_deny_non_admin_users(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->assertTrue(Gate::forUser($admin)->allows('access-admin-panel'));
        $this->assertTrue(Gate::forUser($admin)->allows('assign-prize-manually'));
        $this->assertTrue(Gate::forUser($admin)->allows('auto-assign-prizes'));

        $this->assertTrue(Gate::forUser($user)->denies('access-admin-panel'));
        $this->assertTrue(Gate::forUser($user)->denies('assign-prize-manually'));
        $this->assertTrue(Gate::forUser($user)->denies('auto-assign-prizes'));
    }

    public function test_user_policy_allows_access_to_own_profile_and_prizes(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('viewProfile', $user));
        $this->assertTrue(Gate::forUser($user)->allows('updateProfile', $user));
        $this->assertTrue(Gate::forUser($user)->allows('viewPrizes', $user));
    }

    public function test_user_policy_denies_access_to_other_users_profile_and_prizes(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertTrue(Gate::forUser($user)->denies('viewProfile', $otherUser));
        $this->assertTrue(Gate::forUser($user)->denies('updateProfile', $otherUser));
        $this->assertTrue(Gate::forUser($user)->denies('viewPrizes', $otherUser));
    }
}
