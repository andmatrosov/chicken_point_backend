<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    protected function headers(): int|string
    {
        return config('trustedproxy.headers', parent::headers());
    }
}
