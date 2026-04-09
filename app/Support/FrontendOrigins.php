<?php

namespace App\Support;

class FrontendOrigins
{
    /**
     * @param  array<int, mixed>  $origins
     * @return array<int, string>
     */
    public static function normalize(array $origins): array
    {
        $normalized = [];

        foreach ($origins as $origin) {
            $normalizedOrigin = self::normalizeOrigin($origin);

            if ($normalizedOrigin === null) {
                continue;
            }

            $normalized[$normalizedOrigin] = $normalizedOrigin;
        }

        return array_values($normalized);
    }

    /**
     * @return array<int, string>
     */
    public static function parse(string $origins): array
    {
        return self::normalize(explode(',', $origins));
    }

    public static function normalizeOrigin(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $parts = parse_url($value);

        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || ! self::isValidHost($host)) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $scheme.'://'.$host.$port;
    }

    /**
     * @return array<int, string>
     */
    public static function defaultLocal(): array
    {
        return self::parse(
            'http://localhost:3000,http://127.0.0.1:3000,http://localhost:5173,http://127.0.0.1:5173',
        );
    }

    /**
     * @param  array<int, string>  $origins
     * @return array<int, string>
     */
    public static function patterns(array $origins): array
    {
        return array_map(
            static fn (string $origin): string => '#^'.preg_quote($origin, '#').'$#',
            self::normalize($origins),
        );
    }

    /**
     * @param  array<int, mixed>  $allowedOrigins
     */
    public static function contains(array $allowedOrigins, mixed $origin): bool
    {
        $normalizedOrigin = self::normalizeOrigin($origin);

        if ($normalizedOrigin === null) {
            return false;
        }

        return in_array($normalizedOrigin, self::normalize($allowedOrigins), true);
    }

    private static function isValidHost(string $host): bool
    {
        if ($host === 'localhost') {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        return filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}
