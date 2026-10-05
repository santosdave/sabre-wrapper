<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

/**
 * Cross-process locks (token refresh, session acquisition) on Laravel's atomic cache locks.
 * A lock expires by itself after its timeout, so one left behind by a process that died
 * never blocks the others. Needs a cache store with locks (redis, database, file, array,
 * memcached, dynamodb).
 */
class DistributedLockService
{
    private const LOCK_PREFIX = 'sabre_lock_';
    private const DEFAULT_TIMEOUT = 10; // seconds

    /** @var array<string, Lock> */
    private array $held = [];

    /**
     * @param  string  $scope  the account (user and PCC), so accounts never wait on each other
     */
    public function __construct(private string $scope = '') {}

    public function acquireLock(string $key, int $timeout = self::DEFAULT_TIMEOUT): bool
    {
        $lock = Cache::lock($this->getLockKey($key), $timeout);

        try {
            $lock->block($timeout);
        } catch (LockTimeoutException $e) {
            throw new SabreApiException("Could not acquire lock for: {$key}");
        }

        $this->held[$key] = $lock;

        return true;
    }

    public function releaseLock(string $key): void
    {
        if (isset($this->held[$key])) {
            $this->held[$key]->release();
            unset($this->held[$key]);
        }
    }

    public function withLock(string $key, callable $callback)
    {
        $this->acquireLock($key);

        try {
            return $callback();
        } finally {
            $this->releaseLock($key);
        }
    }

    private function getLockKey(string $key): string
    {
        return self::LOCK_PREFIX . ($this->scope !== '' ? $this->scope . '_' : '') . $key;
    }
}
