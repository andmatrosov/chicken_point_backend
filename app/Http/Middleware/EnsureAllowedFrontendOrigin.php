<?php

namespace App\Http\Middleware;

use App\Support\FrontendOrigins;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAllowedFrontendOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $origin = $request->headers->get('Origin');

        if ($origin === null || $origin === '') {
            return $next($request);
        }

        if (FrontendOrigins::contains(config('cors.allowed_origins', []), $origin)) {
            return $next($request);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Origin is not allowed.',
        ], 403);
    }
}
