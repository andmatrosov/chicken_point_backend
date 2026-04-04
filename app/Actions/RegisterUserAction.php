<?php

namespace App\Actions;

use App\Models\User;
use App\Services\AuthService;
use App\Services\GeoIpService;
use Illuminate\Support\Facades\DB;

class RegisterUserAction
{
    public function __construct(
        protected AuthService $authService,
        protected GeoIpService $geoIpService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{token: string, user: User}
     */
    public function __invoke(array $attributes, ?string $registrationIp = null): array
    {
        return DB::transaction(function () use ($attributes, $registrationIp): array {
            $country = $this->geoIpService->detectCountry($registrationIp);

            $user = User::query()->create([
                'email' => (string) $attributes['email'],
                'registration_ip' => $registrationIp,
                'country_code' => $country['code'] ?? null,
                'country_name' => $country['name'] ?? null,
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
