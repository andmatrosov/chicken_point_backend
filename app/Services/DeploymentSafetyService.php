<?php

namespace App\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\UrlGenerator;
use RuntimeException;

class DeploymentSafetyService
{
    public function __construct(
        protected Application $application,
        protected UrlGenerator $url,
    ) {
    }

    public function enforce(): void
    {
        if (! $this->application->environment('production')) {
            return;
        }

        $this->ensureDebugDisabled();
        $this->ensureHttpsConfiguration();
        $this->ensureRequiredSecrets();

        $this->url->forceScheme('https');
    }

    protected function ensureDebugDisabled(): void
    {
        if ((bool) config('app.debug')) {
            throw new RuntimeException('Application is misconfigured for production.');
        }
    }

    protected function ensureHttpsConfiguration(): void
    {
        $scheme = strtolower((string) parse_url((string) config('app.url'), PHP_URL_SCHEME));

        if ($scheme !== 'https') {
            throw new RuntimeException('Application is misconfigured for production.');
        }
    }

    protected function ensureRequiredSecrets(): void
    {
        $missing = [];

        if (blank(config('app.key'))) {
            $missing[] = 'app.key';
        }

        if ($missing !== []) {
            throw new RuntimeException('Application is misconfigured for production.');
        }
    }
}
