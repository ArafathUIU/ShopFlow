<?php

namespace App\Services\Cache;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class CacheService
{
    public const PRODUCTS_TTL_SECONDS = 3600;

    public const CATEGORIES_TTL_SECONDS = 7200;

    public const CONFIG_TTL_SECONDS = 86400;

    public function __construct(private readonly CacheRepository $cache) {}

    public function remember(string $key, \DateInterval|int|null $ttl, callable $callback): mixed
    {
        return $this->cache->remember($this->prefix($key), $ttl, $callback);
    }

    public function forget(string $key): bool
    {
        return $this->cache->forget($this->prefix($key));
    }

    public function tags(array $tags): self
    {
        $this->cache = $this->cache->tags($tags);

        return $this;
    }

    public function flush(): bool
    {
        return $this->cache->flush();
    }

    private function prefix(string $key): string
    {
        return 'shopflow:'.$key;
    }
}
