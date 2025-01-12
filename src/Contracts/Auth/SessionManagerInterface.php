<?php

namespace Santosdave\SabreWrapper\Contracts\Auth;

interface SessionManagerInterface
{
    /**
     * Get session from pool
     */
    public function getSession(): string;

    /**
     * Release session back to pool
     */
    public function releaseSession(string $token): void;

    /**
     * Check if session is valid
     */
    public function isSessionValid(string $token): bool;
}