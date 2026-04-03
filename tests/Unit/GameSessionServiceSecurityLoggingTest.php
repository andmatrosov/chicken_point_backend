<?php

namespace Tests\Unit;

use App\Enums\GameSessionStatus;
use App\Exceptions\BusinessException;
use App\Models\GameSession;
use App\Models\User;
use App\Services\GameSessionService;
use App\Services\SecurityEventLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class GameSessionServiceSecurityLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_active_session_limit_failures(): void
    {
        config()->set('game.session.max_active_sessions_per_user', 1);

        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'existing-active-session-token',
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'metadata' => ['device_id' => 'ios-existing'],
        ]);

        $this->mock(SecurityEventLogger::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('logBusinessFailure')
                ->once()
                ->withArgs(function (string $event, array $context) use ($user): bool {
                    return $event === 'active_session_limit_reached'
                        && $context['user_id'] === $user->id
                        && $context['active_sessions'] === 1
                        && $context['session_limit'] === 1
                        && $context['metadata_keys'] === ['device_id'];
                });
        });

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Too many active game sessions.');

        app(GameSessionService::class)->startSession($user, [
            'device_id' => 'ios-device-2',
        ]);
    }
}
