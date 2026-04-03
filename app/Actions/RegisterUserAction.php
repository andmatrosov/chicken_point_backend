<?php

namespace App\Actions;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\DB;

class RegisterUserAction
{
    public function __construct(
        protected AuthService $authService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{token: string, user: User}
     */
    public function __invoke(array $attributes): array
    {
        return DB::transaction(function () use ($attributes): array {
            $user = User::query()->create([
                'email' => (string) $attributes['email'],
                'password' => (string) $attributes['password'],
                'best_score' => 0,
                'coins' => 0,
                'active_skin_id' => null,
                'last_rank_cached' => null,
                'is_admin' => false,
            ]);

            $token = $this->authService->issueToken($user, [
                'device_id' => $attributes['device_id'] ?? null,
                'platform' => $attributes['platform'] ?? null,
                'app_version' => $attributes['app_version'] ?? null,
            ]);

            return [
                'token' => $token->plainTextToken,
                'user' => $this->authService->loadAuthenticatedUser($user),
            ];
        });
    }
}
