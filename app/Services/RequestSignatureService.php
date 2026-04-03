<?php

namespace App\Services;

use Illuminate\Http\Request;

class RequestSignatureService
{
    public function __construct(
        protected RequestNonceStore $nonceStore,
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) config('game.signature.enabled', true);
    }

    /**
     * @return array{
     *     timestamp: string,
     *     nonce: string,
     *     signature: string
     * }
     */
    public function extractHeaders(Request $request): array
    {
        return [
            'timestamp' => trim((string) $request->header($this->headerName('timestamp'), '')),
            'nonce' => trim((string) $request->header($this->headerName('nonce'), '')),
            'signature' => trim((string) $request->header($this->headerName('signature'), '')),
        ];
    }

    /**
     * @param  array{
     *     timestamp: string,
     *     nonce: string,
     *     signature: string
     * }  $headers
     * @return array<int, string>
     */
    public function missingHeaders(array $headers): array
    {
        $missing = [];

        foreach (['timestamp', 'nonce', 'signature'] as $key) {
            if ($headers[$key] === '') {
                $missing[] = $this->headerName($key);
            }
        }

        return $missing;
    }

    public function isFreshTimestamp(string $timestamp): bool
    {
        if (! ctype_digit($timestamp)) {
            return false;
        }

        $now = now()->timestamp;
        $maxSkew = (int) config('game.signature.max_skew_seconds', 60);

        return abs($now - (int) $timestamp) <= $maxSkew;
    }

    public function rememberNonce(string $nonce): bool
    {
        return $this->nonceStore->remember(
            $nonce,
            (int) config('game.signature.nonce_ttl_seconds', 120),
        );
    }

    public function hasValidSignature(
        Request $request,
        string $timestamp,
        string $nonce,
        string $providedSignature,
    ): bool {
        $expectedSignature = $this->sign(
            strtoupper($request->getMethod()),
            $this->canonicalPath($request->getPathInfo()),
            $request->getContent(),
            $timestamp,
            $nonce,
        );

        return hash_equals($expectedSignature, $providedSignature);
    }

    public function sign(
        string $method,
        string $path,
        string $body,
        string $timestamp,
        string $nonce,
    ): string {
        return hash_hmac(
            'sha256',
            $this->buildPayload($method, $this->canonicalPath($path), $body, $timestamp, $nonce),
            $this->secret(),
        );
    }

    public function canonicalPath(string $path): string
    {
        $normalizedPath = '/'.ltrim(parse_url($path, PHP_URL_PATH) ?? '/', '/');

        return $normalizedPath === '//' ? '/' : $normalizedPath;
    }

    public function hasConfiguredSecret(): bool
    {
        return $this->secret() !== '';
    }

    protected function buildPayload(
        string $method,
        string $path,
        string $body,
        string $timestamp,
        string $nonce,
    ): string {
        return implode('|', [
            strtoupper($method),
            $path,
            $body,
            $timestamp,
            $nonce,
        ]);
    }

    protected function secret(): string
    {
        return (string) config('game.signature.secret', '');
    }

    protected function headerName(string $key): string
    {
        return (string) config('game.signature.headers.'.$key);
    }
}
