<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Santosdave\SabreWrapper\Exceptions\Auth\SabreAuthenticationException;

class AuthenticationRetryHandler
{
    public function executeWithRetry(callable $operation, string $type): mixed
    {
        $attempts = 0;
        $maxAttempts = config('sabre.auth.retry.max_attempts', 3);

        while ($attempts < $maxAttempts) {
            try {
                return $operation();
            } catch (SabreAuthenticationException $e) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }

                // Calculate delay with exponential backoff
                $delay = $this->calculateDelay($attempts);
                usleep($delay * 1000);

                // Force token refresh
                Cache::forget("sabre_token_{$type}");
            }
        }

        throw new SabreAuthenticationException(
            "Max retry attempts reached for {$type} authentication"
        );
    }

    private function calculateDelay(int $attempt): int
    {
        return min(
            1000 * pow(2, $attempt - 1), // Exponential backoff
            config('sabre.auth.retry.max_delay', 32000)
        );
    }
}