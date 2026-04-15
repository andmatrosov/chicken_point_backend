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

    public function test_it_logs_inactive_session_close_attempts(): void
    {
        $user = User::factory()->create();

        GameSession::query()->create([
            'user_id' => $user->id,
            'token' => 'submitted-session-token',
            'status' => GameSessionStatus::SUBMITTED,
            'issued_at' => now(),
            'expires_at' => null,
            'metadata' => ['device_id' => 'ios-existing'],
        ]);

        $this->mock(SecurityEventLogger::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('logInactiveSessionCloseAttempt')
                ->once()
                ->withArgs(function (User $loggedUser, string $sessionToken, string $status) use ($user): bool {
                    return $loggedUser->is($user)
                        && $sessionToken === 'submitted-session-token'
                        && $status === GameSessionStatus::SUBMITTED->value;
                });
        });

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('This session is not available for closing.');

        app(GameSessionService::class)->closeSession($user, 'submitted-session-token');
    }
}
