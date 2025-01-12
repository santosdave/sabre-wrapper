<?php

namespace Santosdave\SabreWrapper\Contracts\Auth;

interface TokenRotatorInterface
{
    /**
     * Rotate to new token
     */
    public function rotateToken(string $type, string $currentToken, string $newToken): void;

    /**
     * Check if token is still valid after rotation
     */
    public function isValidToken(string $type, string $token): bool;

    /**
     * Clean up old rotated tokens
     */
    public function cleanup(string $type): void;
}