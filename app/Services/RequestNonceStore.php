<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Factory as CacheFactory;

class RequestNonceStore
{
    public function __construct(
        protected CacheFactory $cache,
    ) {
    }

    public function remember(string $nonce, ?int $ttlSeconds = null): bool
    {
        return $this->cache
            ->store($this->storeName())
            ->add($this->key($nonce), true, $ttlSeconds ?? $this->ttlSeconds());
    }

    protected function key(string $nonce): string
    {
        return 'game:request-signature:nonce:'.$nonce;
    }

    protected function storeName(): string
    {
        return (string) config('game.signature.nonce_store', 'redis');
    }

    protected function ttlSeconds(): int
    {
        return (int) config('game.signature.nonce_ttl_seconds', 120);
    }
}
