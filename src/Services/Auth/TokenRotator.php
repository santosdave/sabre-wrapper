<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Santosdave\SabreWrapper\Contracts\Auth\TokenRotatorInterface;

/**
 * Remembers the last tokens issued, per account and token type, so a token that was just
 * replaced is still accepted while requests using it finish.
 */
class TokenRotator implements TokenRotatorInterface
{
    private const TOKEN_PREFIX = 'sabre_token_';
    private const MAX_RETAINED_TOKENS = 2;

    /**
     * @param  string  $scope  the account (user and PCC), so accounts never invalidate each
     *                         other's tokens; empty for the old single, app-wide list
     */
    public function __construct(private string $scope = '') {}

    public function rotateToken(string $type, string $currentToken, string $newToken): void
    {
        $key = $this->key($type);
        $tokens = Cache::get($key, []);

        array_unshift($tokens, $newToken);
        if (count($tokens) > self::MAX_RETAINED_TOKENS) {
            array_pop($tokens);
        }

        Cache::put($key, $tokens, now()->addDay());
    }

    public function isValidToken(string $type, string $token): bool
    {
        $tokens = Cache::get($this->key($type), []);
        return in_array($token, $tokens);
    }

    public function cleanup(string $type): void
    {
        Cache::forget($this->key($type));
    }

    private function key(string $type): string
    {
        return self::TOKEN_PREFIX . ($this->scope !== '' ? $this->scope . '_' : '') . $type;
    }
}
