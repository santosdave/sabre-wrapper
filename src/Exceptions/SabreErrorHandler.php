<?php

namespace Santosdave\Sabre\Exceptions;

use Santosdave\Sabre\Exceptions\Auth\SabreAuthenticationException;

class SabreErrorHandler 
{
    // Sabre specific error codes and their meanings
    private const ERROR_CODES = [
        'ERR.2SG.SEC.MISSING_CREDENTIALS' => ['code' => 401, 'retry' => false],
        'ERR.2SG.SEC.INVALID_CREDENTIALS' => ['code' => 401, 'retry' => false],
        'ERR.2SG.SEC.TOKEN_EXPIRED' => ['code' => 401, 'retry' => true],
        'ERR.2SG.SEC.NOT_AUTHORIZED' => ['code' => 403, 'retry' => false],
        'USG_INVALID_SECURITY_TOKEN' => ['code' => 401, 'retry' => true],
        'USG_AUTHENTICATION_FAILED' => ['code' => 401, 'retry' => true],
        'USG_INVALID_SESSION' => ['code' => 401, 'retry' => true],
        'USG_IS_BUSY' => ['code' => 429, 'retry' => true],
        'ERR.2SG.GATEWAY.TIMEOUT' => ['code' => 504, 'retry' => true],
    ];

    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000; // 1 second

    public static function handleError(string $errorCode, string $message, int $retryCount = 0): void 
    {
        $errorInfo = self::ERROR_CODES[$errorCode] ?? ['code' => 500, 'retry' => false];
        
        // Log the error
        self::logError($errorCode, $message, $errorInfo);

        // Check if we should retry
        if ($errorInfo['retry'] && $retryCount < self::MAX_RETRIES) {
            self::handleRetry($errorCode, $message, $retryCount);
            return;
        }

        // Throw appropriate exception
        self::throwException($errorCode, $message, $errorInfo);
    }

    private static function handleRetry(string $errorCode, string $message, int $retryCount): void 
    {
        // Wait before retry
        usleep(self::RETRY_DELAY_MS * 1000 * ($retryCount + 1));

        // Increment retry count
        $retryCount++;

        // Log retry attempt
        \Illuminate\Support\Facades\Log::info("Retrying Sabre request", [
            'error_code' => $errorCode,
            'attempt' => $retryCount,
            'max_attempts' => self::MAX_RETRIES
        ]);
    }

    private static function logError(string $errorCode, string $message, array $errorInfo): void 
    {
        $context = [
            'error_code' => $errorCode,
            'http_code' => $errorInfo['code'],
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        \Illuminate\Support\Facades\Log::error('Sabre API Error', $context);
    }

    private static function throwException(string $errorCode, string $message, array $errorInfo): void 
    {
        switch ($errorInfo['code']) {
            case 401:
                throw new SabreAuthenticationException($message, $errorInfo['code'], $errorCode);
            case 403:
                throw new SabreAuthorizationException($message, $errorInfo['code'], $errorCode);
            case 429:
                throw new SabreRateLimitException($message, $errorInfo['code'], $errorCode);
            default:
                throw new SabreApiException($message, $errorInfo['code'], ['error_code' => $errorCode]);
        }
    }
}