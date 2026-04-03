<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_a_sanctum_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'PLAYER@Example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'device_id' => 'ios-device-1',
            'platform' => 'ios',
            'app_version' => '1.0.0',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'player@example.com')
            ->assertJsonPath('data.user.best_score', 0)
            ->assertJsonPath('data.user.coins', 0);

        $this->assertDatabaseHas('users', [
            'email' => 'player@example.com',
        ]);

        $user = User::query()->where('email', 'player@example.com')->firstOrFail();

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_user_can_login_and_receive_a_new_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'player@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'player@example.com',
            'password' => 'secret123',
            'device_id' => 'android-device-1',
            'platform' => 'android',
            'app_version' => '2.4.1',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'player@example.com');

        $this->assertSame(1, $user->fresh()->tokens()->count());
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'player@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'player@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials.')
            ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('mobile-client')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message', 'Logged out successfully.');

        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    public function test_me_endpoint_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create([
            'email' => 'player@example.com',
            'best_score' => 350,
            'coins' => 120,
            'is_admin' => true,
        ]);

        $plainTextToken = $user->createToken('mobile-client')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'player@example.com')
            ->assertJsonPath('data.best_score', 350)
            ->assertJsonPath('data.coins', 120)
            ->assertJsonPath('data.is_admin', true);
    }

    public function test_me_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
