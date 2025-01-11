<?php

namespace Santosdave\Sabre\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Santosdave\Sabre\Exceptions\SabreApiException;

class SabreCacheService
{
    private const DEFAULT_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'sabre_cache_';

    private array $config;
    private array $ttlMap;

    public function __construct()
    {
        $this->loadConfig();
        $this->initializeTTLMap();
    }

    private function loadConfig(): void
    {
        $this->config = [
            'enabled' => config('sabre.cache.enabled', true),
            'ttl' => config('sabre.cache.ttl', self::DEFAULT_TTL),
            'prefix' => config('sabre.cache.prefix', self::CACHE_PREFIX)
        ];
    }

    private function initializeTTLMap(): void
    {
        $this->ttlMap = [
            // Shopping and Pricing
            'shopping.results' => 300,       // 5 minutes
            'shopping.alternatives' => 600,   // 10 minutes
            'pricing.quotes' => 900,         // 15 minutes
            'pricing.rules' => 1800,         // 30 minutes

            // Availability and Inventory
            'availability.seats' => 300,     // 5 minutes
            'availability.flights' => 600,   // 10 minutes
            'inventory.status' => 60,        // 1 minute

            // Orders and Bookings
            'orders.view' => 60,            // 1 minute
            'bookings.details' => 300,      // 5 minutes
            'bookings.history' => 1800,     // 30 minutes

            // Reference Data
            'airports.list' => 86400,       // 24 hours
            'airlines.info' => 86400,       // 24 hours
            'equipment.types' => 86400,     // 24 hours

            // Ancillary Services
            'ancillary.services' => 1800,   // 30 minutes
            'ancillary.rules' => 3600,      // 1 hour

            // Schedules
            'schedules.routes' => 3600,     // 1 hour
            'schedules.timetables' => 7200  // 2 hours
        ];
    }

    public function remember(string $key, callable $callback, ?string $type = null, ?int $ttl = null): mixed
    {
        if (!$this->config['enabled']) {
            return $callback();
        }

        $cacheKey = $this->generateCacheKey($key);
        $cacheTTL = $this->getTTL($type, $ttl);

        try {
            return Cache::remember($cacheKey, $cacheTTL, function () use ($callback, $key, $type) {
                $result = $callback();
                $this->logCacheOperation('set', $key, $type);
                return $result;
            });
        } catch (\Exception $e) {
            Log::error('Cache operation failed', [
                'key' => $key,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return $callback();
        }
    }

    public function put(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }

        $cacheKey = $this->generateCacheKey($key);
        try {
            $success = Cache::put($cacheKey, $value, $ttl ?? $this->config['ttl']);
            $this->logCacheOperation('put', $key);
            return $success;
        } catch (\Exception $e) {
            Log::error('Cache put operation failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function get(string $key, $default = null)
    {
        if (!$this->config['enabled']) {
            return $default;
        }

        $cacheKey = $this->generateCacheKey($key);
        try {
            $value = Cache::get($cacheKey, $default);
            $this->logCacheOperation('get', $key);
            return $value;
        } catch (\Exception $e) {
            Log::error('Cache get operation failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    public function forget(string $key): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }

        $cacheKey = $this->generateCacheKey($key);
        try {
            $success = Cache::forget($cacheKey);
            $this->logCacheOperation('forget', $key);
            return $success;
        } catch (\Exception $e) {
            Log::error('Cache forget operation failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function flush(?string $type = null): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        try {
            if ($type) {
                $pattern = $this->config['prefix'] . $type . '_*';
                $this->flushPattern($pattern);
            } else {
                Cache::flush();
            }

            Log::info('Cache flushed', ['type' => $type ?? 'all']);
        } catch (\Exception $e) {
            Log::error('Cache flush operation failed', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function has(string $key): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }

        return Cache::has($this->generateCacheKey($key));
    }

    public function tags(array $tags): self
    {
        $instance = new self();
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags($tags);
        }
        return $instance;
    }

    public function invalidateByPattern(string $pattern): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        $this->flushPattern($this->config['prefix'] . $pattern);
    }

    private function generateCacheKey(string $key): string
    {
        return $this->config['prefix'] . str_replace(['.', ':', '/'], '_', $key);
    }

    private function getTTL(?string $type, ?int $customTTL = null): int
    {
        if ($customTTL !== null) {
            return $customTTL;
        }

        return $this->ttlMap[$type] ?? $this->config['ttl'];
    }

    private function flushPattern(string $pattern): void
    {
        $store = Cache::getStore();

        // Fallback for all cache store types
        $keys = $this->getCacheKeys($pattern);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    private function getCacheKeys(string $pattern): array
    {

        // Implementation depends on cache store being used
        $store = Cache::getStore();

        if (method_exists($store, 'all')) {
            $keys = array_keys($store->all());
            return array_filter($keys, function ($key) use ($pattern) {
                return fnmatch($pattern, $key);
            });
        }

        // Fallback implementation for file cache
        $keys = [];
        $files = glob(storage_path('framework/cache/*' . $pattern . '*'));

        foreach ($files as $file) {
            $keys[] = basename($file);
        }

        return $keys;
    }

    private function logCacheOperation(string $operation, string $key, ?string $type = null): void
    {
        if (config('sabre.cache.logging_enabled', false)) {
            Log::debug('Cache operation', [
                'operation' => $operation,
                'key' => $key,
                'type' => $type,
                'store' => get_class(Cache::getStore())
            ]);
        }
    }
}