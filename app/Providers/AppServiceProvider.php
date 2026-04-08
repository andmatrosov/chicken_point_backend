<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\DeploymentSafetyService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
        app(DeploymentSafetyService::class)->enforce();

        if (! $this->app->isProduction()) {
            Model::shouldBeStrict();
        }

        Gate::policy(User::class, UserPolicy::class);

        Gate::define(
            'access-admin-panel',
            fn (User $user): Response => $user->is_admin
                ? Response::allow()
                : Response::deny('Only admins can perform this action.'),
        );
        Gate::define(
            'assign-prize-manually',
            fn (User $user): Response => $user->is_admin
                ? Response::allow()
                : Response::deny('Only admins can perform this action.'),
        );
        Gate::define(
            'auto-assign-prizes',
            fn (User $user): Response => $user->is_admin
                ? Response::allow()
                : Response::deny('Only admins can perform this action.'),
        );

        RateLimiter::for('api.login', function (Request $request): Limit {
            $email = mb_strtolower(trim((string) $request->input('email', '')));

            return Limit::perMinute((int) config('game.rate_limits.login_per_minute', 5))
                ->by($request->ip().'|'.$email);
        });

        RateLimiter::for('api.register', fn (Request $request): Limit => Limit::perMinute(
            (int) config('game.rate_limits.register_per_minute', 3),
        )->by($request->ip() ?? 'unknown'));

        RateLimiter::for('api.country', fn (Request $request): Limit => Limit::perMinute(
            (int) config('game.rate_limits.country_check_per_minute', 60),
        )->by($request->ip() ?? 'unknown'));

        RateLimiter::for('api.mvp-settings', fn (Request $request): Limit => Limit::perMinute(
            (int) config('game.rate_limits.mvp_settings_per_minute', 60),
        )->by($request->ip() ?? 'unknown'));

        RateLimiter::for('api.profile', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip() ?? 'unknown';

            return Limit::perMinute((int) config('game.rate_limits.profile_per_minute', 60))
                ->by((string) $key);
        });

        RateLimiter::for('api.active-skin', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip() ?? 'unknown';

            return Limit::perMinute((int) config('game.rate_limits.active_skin_per_minute', 20))
                ->by((string) $key);
        });

        RateLimiter::for('api.session-start', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip() ?? 'unknown';

            return Limit::perMinute((int) config('game.rate_limits.session_start_per_minute', 30))
                ->by((string) $key);
        });

        RateLimiter::for('api.submit-score', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip() ?? 'unknown';

            return Limit::perMinute((int) config('game.rate_limits.submit_score_per_minute', 20))
                ->by((string) $key);
        });

        RateLimiter::for('api.buy-skin', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip() ?? 'unknown';

            return Limit::perMinute((int) config('game.rate_limits.buy_skin_per_minute', 10))
                ->by((string) $key);
        });
    }
}
