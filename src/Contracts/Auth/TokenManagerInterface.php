<?php

namespace Santosdave\SabreWrapper\Contracts\Auth;

interface TokenManagerInterface
{
    /**
     * Get token of specified type
     */
    public function getToken(string $type): string;

    /**
     * Force refresh token of specified type
     */
    public function refreshToken(string $type): void;

    /**
     * Check if token is expired
     */
    public function isTokenExpired(string $type): bool;

    /**
     * Get authorization header with token
     */
    public function getAuthorizationHeader(string $type): string;
}
