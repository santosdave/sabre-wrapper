<?php

namespace Santosdave\Sabre\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TokenRefreshManager
{
    private array $refreshThresholds;
    private array $tokenLifetimes;
    private SabreAuthenticator $authenticator;

    public function __construct(SabreAuthenticator $authenticator)
    {
        $this->authenticator = $authenticator;
        $this->loadConfiguration();
    }

    private function loadConfiguration(): void
    {
        // Load from config/sabre.php
        $this->refreshThresholds = [
            'rest' => config('sabre.auth.refresh_thresholds.rest', 300), // 5 minutes before expiry
            'soap_session' => config('sabre.auth.refresh_thresholds.soap_session', 60), // 1 minute before expiry
            'soap_stateless' => config('sabre.auth.refresh_thresholds.soap_stateless', 3600), // 1 hour before expiry
        ];

        $this->tokenLifetimes = [
            'rest' => config('sabre.auth.token_lifetime.rest', 604800), // 7 days
            'soap_session' => config('sabre.auth.token_lifetime.soap_session', 900), // 15 minutes
            'soap_stateless' => config('sabre.auth.token_lifetime.soap_stateless', 604800), // 7 days
        ];
    }

    public function shouldRefreshToken(string $type, string $token): bool
    {
        $tokenData = $this->getTokenData($type, $token);
        if (!$tokenData) {
            return true;
        }

        $timeUntilExpiry = $tokenData['expires_at'] - time();
        return $timeUntilExpiry <= $this->refreshThresholds[$type];
    }

    public function refreshTokenIfNeeded(string $type, string $token): string
    {
        if ($this->shouldRefreshToken($type, $token)) {
            try {
                Log::info("Refreshing {$type} token", ['token_prefix' => substr($token, 0, 10)]);

                $this->authenticator->refreshToken($type);
                $newToken = $this->authenticator->getToken($type);
                $this->storeTokenData($type, $newToken);

                return $newToken;
            } catch (\Exception $e) {
                Log::error("Failed to refresh {$type} token", [
                    'error' => $e->getMessage(),
                    'token_prefix' => substr($token, 0, 10)
                ]);
                throw $e;
            }
        }

        return $token;
    }

    public function storeTokenData(string $type, string $token): string
    {
        $expiresAt = time() + $this->tokenLifetimes[$type];

        Cache::put(
            $this->getTokenCacheKey($type, $token),
            [
                'token' => $token,
                'created_at' => time(),
                'expires_at' => $expiresAt,
                'type' => $type
            ],
            now()->addSeconds($this->tokenLifetimes[$type])
        );

        return $token;
    }

    private function getTokenData(string $type, string $token): ?array
    {
        return Cache::get($this->getTokenCacheKey($type, $token));
    }

    private function getTokenCacheKey(string $type, string $token): string
    {
        return "sabre_token_{$type}_" . md5($token);
    }

    public function setRefreshThreshold(string $type, int $seconds): void
    {
        if (!array_key_exists($type, $this->refreshThresholds)) {
            throw new \InvalidArgumentException("Invalid token type: {$type}");
        }

        $this->refreshThresholds[$type] = $seconds;
    }

    public function setTokenLifetime(string $type, int $seconds): void
    {
        if (!array_key_exists($type, $this->tokenLifetimes)) {
            throw new \InvalidArgumentException("Invalid token type: {$type}");
        }

        $this->tokenLifetimes[$type] = $seconds;
    }
}