<?php

namespace Tests\Unit;

use App\Enums\GameSessionStatus;
use App\Exceptions\BusinessException;
use App\Models\GameSession;
use App\Models\User;
use App\Services\GameSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSessionServiceStartSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_active_sessions_do_not_block_starting_a_new_session(): void
    {
        config()->set('game.session.max_active_sessions_per_user', 1);
        config()->set('game.session.invalidate_previous_active_sessions', false);

        $user = User::factory()->create();

        $expiredSession = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'expired-active-session-token',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinute(),
            'metadata' => ['device_id' => 'ios-old'],
        ]);

        $newSession = app(GameSessionService::class)->startSession($user, [
            'device_id' => 'ios-new',
        ]);

        $this->assertSame(GameSessionStatus::ACTIVE, $newSession->status);
        $this->assertSame(GameSessionStatus::EXPIRED, $expiredSession->fresh()->status);
        $this->assertDatabaseCount('game_sessions', 2);
    }

    public function test_invalidate_previous_active_sessions_runs_before_cap_enforcement(): void
    {
        config()->set('game.session.max_active_sessions_per_user', 1);
        config()->set('game.session.invalidate_previous_active_sessions', true);

        $user = User::factory()->create();

        $existingSession = GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'existing-live-session-token',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(10),
            'metadata' => ['device_id' => 'ios-existing'],
        ]);

        $newSession = app(GameSessionService::class)->startSession($user, [
            'device_id' => 'ios-next',
        ]);

        $this->assertSame(GameSessionStatus::CANCELED, $existingSession->fresh()->status);
        $this->assertSame(GameSessionStatus::ACTIVE, $newSession->status);
        $this->assertNotSame($existingSession->id, $newSession->id);
    }

    public function test_cap_blocks_when_live_active_sessions_already_reach_the_limit(): void
    {
        config()->set('game.session.max_active_sessions_per_user', 1);
        config()->set('game.session.invalidate_previous_active_sessions', false);

        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'live-active-session-token',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'metadata' => ['device_id' => 'ios-existing'],
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Too many active game sessions.');

        app(GameSessionService::class)->startSession($user, [
            'device_id' => 'ios-second',
        ]);
    }

    public function test_a_second_immediate_start_is_rejected_after_the_first_creates_a_live_session(): void
    {
        config()->set('game.session.max_active_sessions_per_user', 1);
        config()->set('game.session.invalidate_previous_active_sessions', false);

        $user = User::factory()->create();

        $firstSession = app(GameSessionService::class)->startSession($user, [
            'device_id' => 'ios-first',
        ]);

        $this->assertSame(GameSessionStatus::ACTIVE, $firstSession->status);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Too many active game sessions.');

        app(GameSessionService::class)->startSession($user, [
            'device_id' => 'ios-second',
        ]);
    }
}
