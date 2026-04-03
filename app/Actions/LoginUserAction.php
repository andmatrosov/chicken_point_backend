<?php

namespace App\Actions;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;

class LoginUserAction
{
    public function __construct(
        protected AuthService $authService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{token: string, user: User}
     */
    public function __invoke(array $credentials): array
    {
        $user = User::query()
            ->where('email', (string) $credentials['email'])
            ->first();

        if ($user === null || ! Hash::check((string) $credentials['password'], $user->password)) {
            throw new BusinessException(
                'Invalid credentials.',
                401,
                ['email' => ['The provided credentials are incorrect.']],
            );
        }

        $token = $this->authService->issueToken($user, [
            'device_id' => $credentials['device_id'] ?? null,
            'platform' => $credentials['platform'] ?? null,
            'app_version' => $credentials['app_version'] ?? null,
        ]);

        return [
            'token' => $token->plainTextToken,
            'user' => $this->authService->loadAuthenticatedUser($user),
        ];
    }
}
