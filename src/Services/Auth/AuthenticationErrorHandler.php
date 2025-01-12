<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Santosdave\SabreWrapper\Exceptions\Auth\SabreAuthenticationException;
use Santosdave\SabreWrapper\Exceptions\SabreRateLimitException;
use Santosdave\SabreWrapper\Events\Auth\AuthenticationFailedEvent;

class AuthenticationErrorHandler {
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 1000; // milliseconds

    public function handleError(\Exception $e, string $type): void {
        if ($e instanceof SabreAuthenticationException) {
            $this->handleAuthenticationError($e, $type);
        } elseif ($e instanceof SabreRateLimitException) {
            $this->handleRateLimitError($e);
        } else {
            throw $e;
        }
    }

    private function handleAuthenticationError(SabreAuthenticationException $e, string $type): void {
        Log::error("Authentication error for type {$type}", [
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ]);

        // Clear cached tokens
        Cache::forget("sabre_token_{$type}");
        
        // Notify monitoring
        event(new AuthenticationFailedEvent($type, $e->getMessage()));
        
        throw $e;
    }

    private function handleRateLimitError(SabreRateLimitException $e): void {
        Log::warning('Rate limit exceeded', [
            'retry_after' => $e->getRetryAfter(),
            'limit' => $e->getLimit()
        ]);

        throw $e;
    }
}