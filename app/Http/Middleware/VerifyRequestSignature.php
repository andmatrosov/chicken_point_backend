<?php

namespace App\Http\Middleware;

use App\Services\RequestSignatureService;
use App\Services\SecurityEventLogger;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyRequestSignature
{
    public function __construct(
        protected RequestSignatureService $requestSignatureService,
        protected SecurityEventLogger $securityEventLogger,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->requestSignatureService->isEnabled()) {
            return $next($request);
        }

        if (! $this->requestSignatureService->hasConfiguredSecret()) {
            return $this->errorResponse('Request verification is unavailable.', 503);
        }

        $headers = $this->requestSignatureService->extractHeaders($request);
        $missingHeaders = $this->requestSignatureService->missingHeaders($headers);

        if ($missingHeaders !== []) {
            $this->securityEventLogger->logSignatureFailure($request, 'missing_headers', [
                'missing_headers' => $missingHeaders,
            ]);

            return $this->errorResponse('Missing required signature headers.', 400);
        }

        if (! $this->requestSignatureService->isFreshTimestamp($headers['timestamp'])) {
            $this->securityEventLogger->logSignatureFailure($request, 'expired_timestamp');

            return $this->errorResponse('Signature timestamp has expired.', 401);
        }

        if (! $this->requestSignatureService->hasValidSignature(
            $request,
            $headers['timestamp'],
            $headers['nonce'],
            $headers['signature'],
        )) {
            $this->securityEventLogger->logSignatureFailure($request, 'invalid_signature');

            return $this->errorResponse('Invalid request signature.', 401);
        }

        if (! $this->requestSignatureService->rememberNonce($headers['nonce'])) {
            $this->securityEventLogger->logNonceReplay($request, $headers['nonce']);

            return $this->errorResponse('Request nonce has already been used.', 409);
        }

        return $next($request);
    }

    protected function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
