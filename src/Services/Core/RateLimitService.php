<?php

namespace Santosdave\SabreWrapper\Services\Core;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Santosdave\SabreWrapper\Exceptions\SabreRateLimitException;

class RateLimitService
{
    private array $config;
    private array $limits;

    public function __construct()
    {
        $this->loadConfig();
        $this->initializeLimits();
    }

    private function loadConfig(): void
    {
        $this->config = [
            'enabled' => config('sabre.rate_limiting.enabled', true),
            'default_window' => config('sabre.rate_limiting.default_window', 60),
            'default_limit' => config('sabre.rate_limiting.default_limit', 100),
            'decay_minutes' => config('sabre.rate_limiting.decay_minutes', 1),
            'cache_prefix' => 'sabre_rate_limit:'
        ];
    }

    private function initializeLimits(): void
    {
        // Define rate limits for different API endpoints/operations
        $this->limits = [
            'shopping' => [
                'bargain_finder_max' => ['limit' => 50, 'window' => 60],
                'alternative_dates' => ['limit' => 30, 'window' => 60],
                'insta_flights' => ['limit' => 40, 'window' => 60]
            ],
            'booking' => [
                'create' => ['limit' => 20, 'window' => 60],
                'modify' => ['limit' => 30, 'window' => 60],
                'cancel' => ['limit' => 25, 'window' => 60]
            ],
            'orders' => [
                'create' => ['limit' => 20, 'window' => 60],
                'fulfill' => ['limit' => 15, 'window' => 60],
                'view' => ['limit' => 100, 'window' => 60]
            ],
            'seats' => [
                'get_map' => ['limit' => 50, 'window' => 60],
                'assign' => ['limit' => 30, 'window' => 60]
            ],
            'authentication' => [
                'token_create' => ['limit' => 10, 'window' => 60],
                'session_create' => ['limit' => 5, 'window' => 60]
            ],
            'default' => [
                'limit' => 100,
                'window' => 60
            ]
        ];
    }

    public function attempt(string $key, int $maxAttempts = null, int $decayMinutes = null): bool
    {
        if (!$this->config['enabled']) {
            return true;
        }

        $limits = $this->getLimits($key);
        $maxAttempts = $maxAttempts ?? $limits['limit'];
        $decayMinutes = $decayMinutes ?? ($limits['window'] / 60);

        $cacheKey = $this->getCacheKey($key);
        $currentAttempts = Cache::get($cacheKey, 0) + 1;

        if ($currentAttempts > $maxAttempts) {
            $this->logRateLimitExceeded($key, $currentAttempts, $maxAttempts);
            throw new SabreRateLimitException(
                "Rate limit exceeded for {$key}",
                429,
                null,
                $maxAttempts,
                0,
                $this->getResetTime($decayMinutes),
                $this->getRetryAfter($decayMinutes)
            );
        }

        Cache::put($cacheKey, $currentAttempts, now()->addMinutes($decayMinutes));
        $this->logAttempt($key, $currentAttempts, $maxAttempts);

        return true;
    }

    public function remaining(string $key): int
    {
        if (!$this->config['enabled']) {
            return PHP_INT_MAX;
        }

        $limits = $this->getLimits($key);
        $currentAttempts = Cache::get($this->getCacheKey($key), 0);
        return max(0, $limits['limit'] - $currentAttempts);
    }

    public function clear(string $key): void
    {
        Cache::forget($this->getCacheKey($key));
    }

    public function reset(string $key): void
    {
        $this->clear($key);
        $this->logReset($key);
    }

    public function getRateLimitInfo(string $key): array
    {
        $limits = $this->getLimits($key);
        $currentAttempts = Cache::get($this->getCacheKey($key), 0);

        return [
            'limit' => $limits['limit'],
            'remaining' => max(0, $limits['limit'] - $currentAttempts),
            'reset' => $this->getResetTime($limits['window'] / 60),
            'window' => $limits['window']
        ];
    }

    private function getLimits(string $key): array
    {
        $parts = explode('.', $key);
        $category = $parts[0] ?? '';
        $operation = $parts[1] ?? '';

        return $this->limits[$category][$operation]
            ?? $this->limits[$category]
            ?? $this->limits['default'];
    }

    private function getCacheKey(string $key): string
    {
        return $this->config['cache_prefix'] . $key;
    }

    private function getResetTime(int $decayMinutes): int
    {
        return now()->addMinutes($decayMinutes)->getTimestamp();
    }

    private function getRetryAfter(int $decayMinutes): string
    {
        return now()->addMinutes($decayMinutes)->toRfc7231String();
    }

    private function logRateLimitExceeded(string $key, int $attempts, int $limit): void
    {
        Log::warning('Rate limit exceeded', [
            'key' => $key,
            'attempts' => $attempts,
            'limit' => $limit,
            'window' => $this->getLimits($key)['window']
        ]);
    }

    private function logAttempt(string $key, int $attempts, int $limit): void
    {
        if ($attempts > ($limit * 0.8)) {
            Log::info('Approaching rate limit', [
                'key' => $key,
                'attempts' => $attempts,
                'limit' => $limit
            ]);
        }
    }

    private function logReset(string $key): void
    {
        Log::info('Rate limit reset', ['key' => $key]);
    }
}
