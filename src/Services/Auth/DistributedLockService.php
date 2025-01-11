<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class DistributedLockService
{
    private const LOCK_PREFIX = 'sabre_lock_';
    private const DEFAULT_TIMEOUT = 10; // seconds
    private const DEFAULT_RETRY_COUNT = 3;
    private const RETRY_DELAY = 100; // milliseconds

    public function acquireLock(string $key, int $timeout = self::DEFAULT_TIMEOUT): bool
    {
        $lockKey = $this->getLockKey($key);
        $retryCount = 0;

        while ($retryCount < self::DEFAULT_RETRY_COUNT) {
            if (Cache::add($lockKey, true, $timeout)) {
                Log::debug('Lock acquired', ['key' => $key]);
                return true;
            }

            $retryCount++;
            usleep(self::RETRY_DELAY * 1000);
        }

        Log::warning('Failed to acquire lock', [
            'key' => $key,
            'timeout' => $timeout,
            'retries' => $retryCount
        ]);

        return false;
    }

    public function releaseLock(string $key): void
    {
        $lockKey = $this->getLockKey($key);
        Cache::forget($lockKey);
        Log::debug('Lock released', ['key' => $key]);
    }

    public function withLock(string $key, callable $callback)
    {
        if (!$this->acquireLock($key)) {
            throw new SabreApiException("Could not acquire lock for: {$key}");
        }

        try {
            return $callback();
        } finally {
            $this->releaseLock($key);
        }
    }

    private function getLockKey(string $key): string
    {
        return self::LOCK_PREFIX . $key;
    }
}
