<?php

namespace Santosdave\SabreWrapper\Services\Core;

use Illuminate\Support\Facades\Log;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Exceptions\SabreRateLimitException;
use Santosdave\SabreWrapper\Exceptions\Auth\SabreAuthenticationException;

class RetryService
{
    private const RETRYABLE_EXCEPTIONS = [
        SabreRateLimitException::class,
        SabreAuthenticationException::class
    ];

    private const RETRYABLE_HTTP_CODES = [
        429, // Too Many Requests
        500, // Internal Server Error
        502, // Bad Gateway
        503, // Service Unavailable
        504  // Gateway Timeout
    ];

    private array $config;

    public function __construct()
    {
        $this->config = [
            'max_attempts' => config('sabre.request.retries', 3),
            'base_delay' => config('sabre.request.retry_delay', 1000),
            'max_delay' => 32000, // Maximum delay between retries
            'multiplier' => 2, // Exponential backoff multiplier
            'jitter' => true // Add randomness to delays
        ];
    }

    public function execute(callable $operation, array $context = []): mixed
    {
        $attempt = 1;
        $delay = $this->config['base_delay'];

        while (true) {
            try {
                return $operation();
            } catch (\Exception $e) {
                if (!$this->shouldRetry($e, $attempt)) {
                    throw $e;
                }

                $this->logRetryAttempt($e, $attempt, $context);

                if ($attempt >= $this->config['max_attempts']) {
                    throw new SabreApiException(
                        "Max retry attempts reached: " . $e->getMessage(),
                        $e->getCode(),
                        null,
                        null,
                        null,
                        $e
                    );
                }

                $this->sleep($this->calculateDelay($delay, $attempt));
                $delay = min($delay * $this->config['multiplier'], $this->config['max_delay']);
                $attempt++;
            }
        }
    }

    private function shouldRetry(\Exception $e, int $attempt): bool
    {
        // Check if we've exceeded max attempts
        if ($attempt >= $this->config['max_attempts']) {
            return false;
        }

        // Check if exception is explicitly retryable
        if ($this->isRetryableException($e)) {
            return true;
        }

        // Check HTTP status code if it's an API exception
        if ($e instanceof SabreApiException) {
            return in_array($e->getCode(), self::RETRYABLE_HTTP_CODES);
        }

        return false;
    }

    private function isRetryableException(\Exception $e): bool
    {
        foreach (self::RETRYABLE_EXCEPTIONS as $retryableException) {
            if ($e instanceof $retryableException) {
                // Special handling for rate limit exceptions
                if ($e instanceof SabreRateLimitException) {
                    return $e->shouldRetry();
                }
                return true;
            }
        }
        return false;
    }

    private function calculateDelay(int $baseDelay, int $attempt): int
    {
        $delay = $baseDelay * pow($this->config['multiplier'], $attempt - 1);

        if ($this->config['jitter']) {
            // Add random jitter between 0-30% of the delay
            $jitter = rand(0, 30) / 100 * $delay;
            $delay += $jitter;
        }

        return min((int)$delay, $this->config['max_delay']);
    }

    private function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }

    private function logRetryAttempt(\Exception $e, int $attempt, array $context): void
    {
        Log::warning('Retrying Sabre API request', [
            'attempt' => $attempt,
            'max_attempts' => $this->config['max_attempts'],
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'context' => $context
        ]);
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }
}
