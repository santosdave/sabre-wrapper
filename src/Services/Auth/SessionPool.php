<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Santosdave\SabreWrapper\Exceptions\Auth\SabreAuthenticationException;

class SessionPool
{
    private const POOL_SIZE = 5;
    private const SESSION_PREFIX = 'sabre_session_';
    private const POOL_KEY = 'sabre_session_pool';
    private const LOCK_KEY = 'sabre_session_lock';
    private const LOCK_TTL = 10; // seconds

    private SabreAuthenticator $authenticator;

    public function __construct(SabreAuthenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function getSession(): string
    {
        // Try to get an existing session
        $session = $this->getAvailableSession();

        if ($session) {
            return $session;
        }

        // Create new session if pool isn't full
        return $this->createNewSession();
    }

    public function releaseSession(string $token): void
    {
        if ($this->acquireLock()) {
            try {
                $pool = $this->getPool();
                $sessionKey = $this->findSessionKey($token);

                if ($sessionKey) {
                    // Mark session as available
                    $pool[$sessionKey]['in_use'] = false;
                    $pool[$sessionKey]['last_used'] = time();
                    $this->updatePool($pool);
                }
            } finally {
                $this->releaseLock();
            }
        }
    }

    public function refreshSessions(): void
    {
        if ($this->acquireLock()) {
            try {
                $pool = $this->getPool();

                foreach ($pool as $key => $session) {
                    if ($this->shouldRefreshSession($session)) {
                        $this->refreshSession($key, $session);
                    }
                }
            } finally {
                $this->releaseLock();
            }
        }
    }

    private function getAvailableSession(): ?string
    {
        if ($this->acquireLock()) {
            try {
                $pool = $this->getPool();

                foreach ($pool as $key => $session) {
                    if (!$session['in_use'] && !$this->isSessionExpired($session)) {
                        // Mark session as in use
                        $pool[$key]['in_use'] = true;
                        $pool[$key]['last_used'] = time();
                        $this->updatePool($pool);

                        return $session['token'];
                    }
                }
            } finally {
                $this->releaseLock();
            }
        }

        return null;
    }

    private function createNewSession(): string
    {
        if ($this->acquireLock()) {
            try {
                $pool = $this->getPool();

                // Check if pool is full
                if (count($pool) >= self::POOL_SIZE) {
                    throw new SabreAuthenticationException('Session pool is full');
                }

                // Create new session
                $token = $this->authenticator->getToken('soap_session');
                $sessionKey = self::SESSION_PREFIX . uniqid();

                $pool[$sessionKey] = [
                    'token' => $token,
                    'created_at' => time(),
                    'last_used' => time(),
                    'in_use' => true
                ];

                $this->updatePool($pool);
                return $token;
            } finally {
                $this->releaseLock();
            }
        }

        throw new SabreAuthenticationException('Could not acquire lock to create session');
    }

    private function refreshSession(string $key, array $session): void
    {
        try {
            $this->authenticator->refreshToken('soap_session');
            $newToken = $this->authenticator->getToken('soap_session');

            $pool = $this->getPool();
            $pool[$key]['token'] = $newToken;
            $pool[$key]['last_refreshed'] = time();

            $this->updatePool($pool);

            Log::info('Refreshed Sabre session', ['key' => $key]);
        } catch (\Exception $e) {
            Log::error('Failed to refresh Sabre session', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function shouldRefreshSession(array $session): bool
    {
        $lastRefresh = $session['last_refreshed'] ?? $session['created_at'];
        return (time() - $lastRefresh) > (14 * 60); // 14 minutes
    }

    private function isSessionExpired(array $session): bool
    {
        $createdAt = $session['created_at'];
        return (time() - $createdAt) > (15 * 60); // 15 minutes
    }

    private function getPool(): array
    {
        return Cache::get(self::POOL_KEY, []);
    }

    private function updatePool(array $pool): void
    {
        Cache::put(self::POOL_KEY, $pool, now()->addHours(1));
    }

    private function findSessionKey(string $token): ?string
    {
        $pool = $this->getPool();
        foreach ($pool as $key => $session) {
            if ($session['token'] === $token) {
                return $key;
            }
        }
        return null;
    }

    private function acquireLock(): bool
    {
        return Cache::add(self::LOCK_KEY, true, self::LOCK_TTL);
    }

    private function releaseLock(): void
    {
        Cache::forget(self::LOCK_KEY);
    }

    public function cleanup(): void
    {
        if ($this->acquireLock()) {
            try {
                $pool = $this->getPool();
                foreach ($pool as $key => $session) {
                    if ($this->isSessionExpired($session)) {
                        unset($pool[$key]);
                    }
                }
                $this->updatePool($pool);
            } finally {
                $this->releaseLock();
            }
        }
    }
}
