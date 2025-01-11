<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Santosdave\SabreWrapper\Exceptions\Auth\SabreAuthenticationException;
use Santosdave\SabreWrapper\Models\Auth\TokenRequest;
use Santosdave\SabreWrapper\Models\Auth\TokenResponse;

class TokenRotationService
{
    private const TOKEN_ROTATION_KEY = 'sabre_token_rotation_';
    private const MAX_TOKENS = 2; // Keep current and previous token

    public function __construct(
        private TokenRefreshManager $tokenManager,
        private SabreAuthenticator $authenticator
    ) {}

    public function rotateToken(string $type = 'rest'): string
    {
        try {
            // Get current token before creating new one
            $currentToken = $this->authenticator->getToken($type);

            // Create new token
            $this->authenticator->refreshToken($type);
            $newToken = $this->authenticator->getToken($type);

            // Store both tokens
            $this->storeRotatedTokens($type, $currentToken, $newToken);

            Log::info('Token rotated successfully', [
                'type' => $type,
                'token_prefix' => substr($newToken, 0, 10)
            ]);

            return $newToken;
        } catch (\Exception $e) {
            Log::error('Token rotation failed', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw new SabreAuthenticationException(
                "Failed to rotate token: " . $e->getMessage(),
                401,
                $type
            );
        }
    }

    public function validateToken(string $token, string $type = 'rest'): bool
    {
        $rotatedTokens = $this->getRotatedTokens($type);
        return in_array($token, $rotatedTokens);
    }

    private function storeRotatedTokens(string $type, string $currentToken, string $newToken): void
    {
        $key = self::TOKEN_ROTATION_KEY . $type;
        $tokens = array_slice(
            array_merge([$newToken, $currentToken], $this->getRotatedTokens($type)),
            0,
            self::MAX_TOKENS
        );

        Cache::put($key, $tokens, now()->addDays(1));
    }

    private function getRotatedTokens(string $type): array
    {
        return Cache::get(self::TOKEN_ROTATION_KEY . $type, []);
    }

    public function cleanupRotatedTokens(string $type = 'rest'): void
    {
        Cache::forget(self::TOKEN_ROTATION_KEY . $type);
    }
}
