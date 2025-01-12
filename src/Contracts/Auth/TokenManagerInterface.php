<?php

namespace Santosdave\SabreWrapper\Contracts\Auth;

interface TokenManagerInterface
{
    /**
     * Get token of specified type
     */
    public function getToken(string $type = 'rest'): string;

    /**
     * Force refresh token of specified type
     */
    public function refreshToken(string $type = 'rest'): void;

    /**
     * Check if token is expired
     */
    public function isTokenExpired(string $type = 'rest'): bool;

    /**
     * Get authorization header with token
     */
    public function getAuthorizationHeader(string $type = 'rest'): string;
}