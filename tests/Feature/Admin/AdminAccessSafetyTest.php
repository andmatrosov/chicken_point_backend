<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAccessSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::patch('/api/test/admin-access/users/{user}', function (Request $request, string $user) {
            $targetUser = User::query()->findOrFail($user);

            $targetUser->update([
                'is_admin' => $request->boolean('is_admin'),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $targetUser->id,
                    'is_admin' => $targetUser->refresh()->is_admin,
                ],
                'meta' => [],
            ]);
        });
    }

    public function test_backend_guard_rejects_self_demotion_with_a_validation_response(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/test/admin-access/users/{$admin->id}", [
                'is_admin' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('errors.is_admin.0', 'You cannot remove your own admin access.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_admin' => true,
        ]);
    }

    public function test_backend_guard_rejects_last_admin_demotion_with_a_validation_response(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/test/admin-access/users/{$admin->id}", [
                'is_admin' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('errors.is_admin.0', 'At least one admin must remain in the system.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_admin' => true,
        ]);
    }

    public function test_backend_guard_allows_demoting_an_admin_when_another_admin_remains(): void
    {
        $actingAdmin = User::factory()->create([
            'is_admin' => true,
        ]);

        $demotedAdmin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($actingAdmin)
            ->patchJson("/api/test/admin-access/users/{$demotedAdmin->id}", [
                'is_admin' => false,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $demotedAdmin->id)
            ->assertJsonPath('data.is_admin', false);

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
