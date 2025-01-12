<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Santosdave\SabreWrapper\Exceptions\Auth\SabreAuthenticationException;

class SessionManager
{
    private const MAX_SESSIONS = 5;

    private array $activeSessions = [];
    private DistributedLockService $lockService;

    public function __construct(DistributedLockService $lockService)
    {
        $this->lockService = $lockService;
    }

    public function acquireSession(): string
    {
        return $this->lockService->withLock('session_acquisition', function () {
            // Try to get available session
            $session = $this->getAvailableSession();
            if ($session) {
                return $session;
            }

            // Create new if pool not full
            if (count($this->activeSessions) < self::MAX_SESSIONS) {
                return $this->createNewSession();
            }

            throw new SabreAuthenticationException('No sessions available');
        });
    }

    private function getAvailableSession(): ?string
    {
        foreach ($this->activeSessions as $key => $session) {
            if (!$session['in_use'] && !$this->isExpired($session)) {
                return $this->markSessionInUse($key);
            }
        }
        return null;
    }

    private function isExpired(array $session): bool
    {
        $lifetime = config('sabre.auth.token_lifetime.soap_session', 900);
        return (time() - $session['created_at']) > $lifetime;
    }

    private function markSessionInUse(string $key): string
    {
        $this->activeSessions[$key]['in_use'] = true;
        $this->activeSessions[$key]['last_used'] = time();
        return $this->activeSessions[$key]['token'];
    }
}