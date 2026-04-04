<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use Illuminate\Http\Request;
use Throwable;

class GeoIpService
{
    protected ?Reader $reader = null;

    public function __construct(
        protected ?string $databasePath = null,
    ) {
        $this->databasePath ??= (string) config('geoip.country_database_path');
    }

    public function __destruct()
    {
        $this->reader?->close();
    }

    public function detectCountryCode(?string $ip): ?string
    {
        return $this->detectCountry($ip)['code'] ?? null;
    }

    /**
     * @return array{code: string, name: string}|null
     */
    public function detectCountry(?string $ip): ?array
    {
        $ip = is_string($ip) ? trim($ip) : null;

        if (! $this->shouldLookupIp($ip) || ! $this->databaseExists()) {
            return null;
        }

        try {
            return $this->lookupCountry($ip);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{code: string, name: string}|null
     */
    public function detectFromRequest(Request $request): ?array
    {
        return $this->detectCountry($request->ip());
    }

    protected function shouldLookupIp(?string $ip): bool
    {
        if ($ip === null || $ip === '' || strcasecmp($ip, 'localhost') === 0) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }

    protected function databaseExists(): bool
    {
        return $this->databasePath !== null
            && $this->databasePath !== ''
            && is_file($this->databasePath)
            && is_readable($this->databasePath);
    }

    /**
     * @return array{code: string, name: string}|null
     */
    protected function lookupCountry(string $ip): ?array
    {
        $record = $this->getReader()->country($ip);
        $code = $record->country->isoCode;
        $name = $record->country->name;

        if (! is_string($code) || $code === '' || ! is_string($name) || $name === '') {
            return null;
        }

        return [
            'code' => $code,
            'name' => $name,
        ];
    }

    protected function getReader(): Reader
    {
        return $this->reader ??= new Reader($this->databasePath);
    }
}
