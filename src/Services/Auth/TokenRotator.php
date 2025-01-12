<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Illuminate\Support\Facades\Cache;

class TokenRotator
{
    private const TOKEN_PREFIX = 'sabre_token_';
    private const MAX_RETAINED_TOKENS = 2;

    public function rotateToken(string $type, string $currentToken, string $newToken): void
    {
        $key = self::TOKEN_PREFIX . $type;
        $tokens = Cache::get($key, []);

        array_unshift($tokens, $newToken);
        if (count($tokens) > self::MAX_RETAINED_TOKENS) {
            array_pop($tokens);
        }

        Cache::put($key, $tokens, now()->addDay());
    }

    public function isValidToken(string $type, string $token): bool
    {
        $tokens = Cache::get(self::TOKEN_PREFIX . $type, []);
        return in_array($token, $tokens);
    }
}