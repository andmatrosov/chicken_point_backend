<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    /**
     * @param  array<string, mixed>  $deviceContext
     */
    public function issueToken(User $user, array $deviceContext = []): NewAccessToken
    {
        return $user->createToken($this->buildTokenName($deviceContext));
    }

    public function loadAuthenticatedUser(User $user): User
    {
        return $user->fresh()->load('activeSkin')->loadCount('skins');
    }

    /**
     * @param  array<string, mixed>  $deviceContext
     */
    protected function buildTokenName(array $deviceContext): string
    {
        $parts = array_filter([
            $this->sanitizeTokenSegment($deviceContext['platform'] ?? null),
            $this->sanitizeTokenSegment($deviceContext['device_id'] ?? null),
            $this->sanitizeTokenSegment($deviceContext['app_version'] ?? null),
        ]);

        return $parts === [] ? 'mobile-client' : implode(':', $parts);
    }

    protected function sanitizeTokenSegment(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return substr(preg_replace('/[^A-Za-z0-9._:-]+/', '-', $value) ?? '', 0, 64);
    }
}
