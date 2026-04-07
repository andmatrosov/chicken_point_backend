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
     * @param  array{
     *     email: string,
     *     password: string,
     *     device_context: array{
     *         device_id: string,
     *         platform: string,
     *         app_version: string
     *     }
     * }  $credentials
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

        $token = $this->authService->issueApiToken($user, $credentials['device_context']);

        return [
            'token' => $token->plainTextToken,
            'user' => $this->authService->loadAuthenticatedUser($user),
        ];
    }
}
