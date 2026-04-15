<?php

namespace App\Services;

use App\Enums\GameSessionStatus;
use App\Exceptions\BusinessException;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GameSessionService
{
    public function __construct(
        protected SecurityEventLogger $securityEventLogger,
    ) {
    }

    public function startSession(User $user, array $metadata = []): GameSession
    {
        $normalizedMetadata = $this->normalizeSessionMetadata($metadata);

        return DB::transaction(function () use ($user, $normalizedMetadata): GameSession {
            User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $activeSessions = GameSession::query()
                ->where('user_id', $user->id)
                ->where('status', GameSessionStatus::ACTIVE)
                ->lockForUpdate()
                ->get();

            $this->cancelActiveSessions($activeSessions);

            $issuedAt = now();

            return GameSession::query()->create([
                'user_id' => $user->id,
                'token' => $this->generateUniqueToken(),
                'status' => GameSessionStatus::ACTIVE,
                'issued_at' => $issuedAt,
                'expires_at' => null,
                'metadata' => $normalizedMetadata,
            ]);
        });
    }

    public function closeSession(User $user, string $sessionToken): GameSession
    {
        return DB::transaction(function () use ($user, $sessionToken): GameSession {
            $gameSession = GameSession::query()
                ->where('token', $sessionToken)
                ->lockForUpdate()
                ->first();

            if ($gameSession === null) {
                $this->securityEventLogger->logSessionCloseNotFound($user, $sessionToken);

                throw new BusinessException(
                    'Invalid game session.',
                    errors: ['session_token' => ['The provided session token is invalid.']],
                );
            }

            if ($gameSession->user_id !== $user->id) {
                $this->securityEventLogger->logForeignSessionCloseAttempt($user, $sessionToken, $gameSession->user_id);

                throw new BusinessException(
                    'This session does not belong to the current user.',
                    403,
                    ['session_token' => ['The provided session token belongs to another user.']],
                );
            }

            if ($gameSession->status !== GameSessionStatus::ACTIVE) {
                $this->securityEventLogger->logInactiveSessionCloseAttempt(
                    $user,
                    $sessionToken,
                    $gameSession->status->value,
                );

                throw new BusinessException(
                    'This session is not available for closing.',
                    errors: ['session_token' => ['The provided session token is not active.']],
                );
            }

            $gameSession->forceFill([
                'status' => GameSessionStatus::CANCELED,
            ])->save();

            return $gameSession->fresh();
        });
    }

    public function normalizeSessionMetadata(array $metadata): ?array
    {
        $normalized = [];

        foreach (['device_id', 'platform', 'app_version'] as $key) {
            if (! array_key_exists($key, $metadata) || ! is_string($metadata[$key])) {
                continue;
            }

            $value = trim($metadata[$key]);

            if ($key === 'platform') {
                $value = mb_strtolower($value);
            }

            if ($value === '') {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized === [] ? null : $normalized;
    }

    protected function generateUniqueToken(): string
    {
        $tokenLength = max(16, (int) config('game.session.token_length', 64));

        do {
            $token = substr(bin2hex(random_bytes((int) ceil($tokenLength / 2))), 0, $tokenLength);
        } while (GameSession::query()->where('token', $token)->exists());

        return $token;
    }

    protected function cancelActiveSessions(iterable $activeSessions): void
    {
        $activeSessionIds = collect($activeSessions)
            ->pluck('id')
            ->all();

        if ($activeSessionIds === []) {
            return;
        }

        GameSession::query()
            ->whereKey($activeSessionIds)
            ->update([
                'status' => GameSessionStatus::CANCELED,
            ]);
    }
}
