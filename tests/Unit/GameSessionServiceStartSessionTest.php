<?php

namespace Tests\Unit;

use App\Enums\GameSessionStatus;
use App\Models\GameSession;
use App\Models\User;
use App\Services\GameSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSessionServiceStartSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_session_cancels_existing_active_sessions_before_creating_a_new_one(): void
    {
        $user = User::factory()->create();

        $existingSession = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'existing-active-session-token',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subMinutes(20),
            'expires_at' => null,
            'metadata' => ['device_id' => 'ios-existing'],
        ]);

        $newSession = app(GameSessionService::class)->startSession($user, [
            'device_id' => 'ios-new',
        ]);

        $this->assertSame(GameSessionStatus::CANCELED, $existingSession->fresh()->status);
        $this->assertSame(GameSessionStatus::ACTIVE, $newSession->status);
        $this->assertDatabaseCount('game_sessions', 2);
    }

    public function test_start_session_creates_a_non_expiring_active_session(): void
    {
        $user = User::factory()->create();

        $gameSession = app(GameSessionService::class)->startSession($user, [
            'device_id' => 'ios-device-1',
            'platform' => 'ios',
        ]);

        $this->assertSame(GameSessionStatus::ACTIVE, $gameSession->status);
        $this->assertNull($gameSession->expires_at);
        $this->assertSame([
            'device_id' => 'ios-device-1',
            'platform' => 'ios',
        ], $gameSession->metadata);
    }
}
