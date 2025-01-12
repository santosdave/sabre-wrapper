<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class DistributedLockService
{
    private const LOCK_PREFIX = 'sabre_lock_';
    private const DEFAULT_TIMEOUT = 10; // seconds
    private const RETRY_INTERVAL = 100; // milliseconds
    private const MAX_RETRIES = 50;

    public function acquireLock(string $key, int $timeout = self::DEFAULT_TIMEOUT): bool
    {
        $lockKey = $this->getLockKey($key);
        $retries = 0;

        while ($retries < self::MAX_RETRIES) {
            if (Cache::add($lockKey, [
                'owner' => uniqid(),
                'acquired_at' => time()
            ], $timeout)) {
                return true;
            }

            // Check if lock is stale
            $lock = Cache::get($lockKey);
            if ($lock && (time() - $lock['acquired_at']) > $timeout) {
                // Try to take over stale lock
                if (Cache::getAndSet($lockKey, function ($old) {
                    return $old && (time() - $old['acquired_at']) > self::DEFAULT_TIMEOUT
                        ? ['owner' => uniqid(), 'acquired_at' => time()]
                        : $old;
                }, $timeout)) {
                    return true;
                }
            }

            $retries++;
            usleep(self::RETRY_INTERVAL * 1000);
        }

        throw new SabreApiException("Could not acquire lock for: {$key}");
    }

    public function releaseLock(string $key): void
    {
        $lockKey = $this->getLockKey($key);
        Cache::forget($lockKey);
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