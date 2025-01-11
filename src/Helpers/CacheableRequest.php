<?php

namespace Santosdave\SabreWrapper\Helpers;

use Santosdave\SabreWrapper\Services\Cache\SabreCacheService;

trait CacheableRequest
{
    private SabreCacheService $cacheService;
    private bool $cacheEnabled = true;

    protected function withCache(string $key, callable $callback, ?string $type = null, ?int $ttl = null)
    {
        if (!$this->cacheEnabled) {
            return $callback();
        }

        return $this->getCacheService()->remember($key, $callback, $type, $ttl);
    }

    protected function invalidateCache(string $key): void
    {
        $this->getCacheService()->forget($key);
    }

    protected function getCacheKey(string ...$parts): string
    {
        return implode(':', array_filter($parts));
    }

    protected function disableCache(): self
    {
        $this->cacheEnabled = false;
        return $this;
    }

    protected function enableCache(): self
    {
        $this->cacheEnabled = true;
        return $this;
    }

    private function getCacheService(): SabreCacheService
    {
        if (!isset($this->cacheService)) {
            $this->cacheService = new SabreCacheService();
        }
        return $this->cacheService;
    }

    protected function generateCacheKey(array $params): string
    {
        ksort($params);
        return md5(serialize($params));
    }

    protected function invalidateCachePattern(string $pattern): void
    {
        $this->getCacheService()->invalidateByPattern($pattern);
    }

    protected function cacheResponse(string $key, $response, ?string $type = null): void
    {
        if ($this->cacheEnabled && $response) {
            $this->getCacheService()->put($key, $response, null);
        }
    }
}
