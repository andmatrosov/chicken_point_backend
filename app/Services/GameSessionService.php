<?php

namespace App\Services;

use App\Enums\GameSessionStatus;
use App\Exceptions\BusinessException;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Support\Carbon;

class GameSessionService
{
    public function __construct(
        protected SecurityEventLogger $securityEventLogger,
    ) {
    }

    public function startSession(User $user, array $metadata = []): GameSession
    {
        $maxActiveSessionsPerUser = filter_var(
            config('game.session.max_active_sessions_per_user'),
            FILTER_VALIDATE_INT,
        );

        if ($maxActiveSessionsPerUser !== false && $maxActiveSessionsPerUser > 0) {
            $activeSessionsCount = GameSession::query()
                ->where('user_id', $user->id)
                ->where('status', GameSessionStatus::ACTIVE)
                ->count();

            if ($activeSessionsCount >= $maxActiveSessionsPerUser) {
                $this->securityEventLogger->logBusinessFailure('active_session_limit_reached', [
                    'user_id' => $user->id,
                    'active_sessions' => $activeSessionsCount,
                    'session_limit' => $maxActiveSessionsPerUser,
                    'metadata_keys' => array_keys($metadata),
                ]);

                throw new BusinessException(
                    'Too many active game sessions.',
                    errors: ['session' => ['The user has reached the active session limit.']],
                );
            }
        }

        if ((bool) config('game.session.invalidate_previous_active_sessions', false)) {
            GameSession::query()
                ->where('user_id', $user->id)
                ->where('status', GameSessionStatus::ACTIVE)
                ->update([
                    'status' => GameSessionStatus::CANCELED,
                ]);
        }

        $issuedAt = now();

        return GameSession::query()->create([
            'user_id' => $user->id,
            'token' => $this->generateUniqueToken(),
            'status' => GameSessionStatus::ACTIVE,
            'issued_at' => $issuedAt,
            'expires_at' => $issuedAt->copy()->addSeconds((int) config('game.session.ttl_seconds', 900)),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    public function isExpired(GameSession $gameSession, ?Carbon $now = null): bool
    {
        return $gameSession->expires_at->lessThanOrEqualTo($now ?? now());
    }

    protected function generateUniqueToken(): string
    {
        $tokenLength = max(16, (int) config('game.session.token_length', 64));

        do {
            $token = substr(bin2hex(random_bytes((int) ceil($tokenLength / 2))), 0, $tokenLength);
        } while (GameSession::query()->where('token', $token)->exists());

        return $token;
    }
}
