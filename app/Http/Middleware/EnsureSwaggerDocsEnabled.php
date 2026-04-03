<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSwaggerDocsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) config('l5-swagger.defaults.routes.enabled', false), 404);

        return $next($request);
    }
}
