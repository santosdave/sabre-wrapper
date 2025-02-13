<?php

namespace Santosdave\SabreWrapper\Services\Logging;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;

class SabreLogger implements LoggerInterface
{
    private Logger $logger;
    private array $context;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'channel' => 'sabre',
            'path' => storage_path('logs/sabre.log'),
            'level' => Logger::DEBUG,
            'days' => 7,
            'separate_files' => [
                'auth' => true,
                'requests' => true,
                'errors' => true
            ]
        ], $config);

        $this->setupLogger();
        $this->context = [];
    }

    private function setupLogger(): void
    {
        $this->logger = new Logger($this->config['channel']);

        // Main log handler with JSON formatting
        $mainHandler = new RotatingFileHandler(
            $this->config['path'],
            $this->config['days'],
            $this->config['level']
        );
        $mainHandler->setFormatter(new JsonFormatter());
        $this->logger->pushHandler($mainHandler);

        // Separate handlers for different types of logs
        if ($this->config['separate_files']['auth']) {
            $authHandler = new RotatingFileHandler(
                storage_path('logs/sabre_auth.log'),
                $this->config['days']
            );
            $authHandler->setFormatter(new JsonFormatter());
            $this->logger->pushHandler($authHandler);
        }

        // if ($this->config['separate_files']['requests']) {
        //     $requestHandler = new RotatingFileHandler(
        //         storage_path('logs/sabre_requests.log'),
        //         $this->config['days']
        //     );
        //     $requestHandler->setFormatter(new JsonFormatter());
        //     $this->logger->pushHandler($requestHandler);
        // }

        // if ($this->config['separate_files']['errors']) {
        //     $errorHandler = new RotatingFileHandler(
        //         storage_path('logs/sabre_errors.log'),
        //         $this->config['days'],
        //         Logger::ERROR
        //     );
        //     $errorHandler->setFormatter(new JsonFormatter());
        //     $this->logger->pushHandler($errorHandler);
        // }
    }

    public function logRequest(string $service, string $action, array $request, array $context = []): void
    {
        $logContext = array_merge($this->context, $context, [
            'service' => $service,
            'action' => $action,
            'request' => $this->sanitizeData($request),
            'timestamp' => now()->toIso8601String(),
            'type' => 'request'
        ]);

        $this->info('Sabre API Request', $logContext);
    }

    public function logResponse(string $service, string $action, array $response, float $duration, array $context = []): void
    {
        $logContext = array_merge($this->context, $context, [
            'service' => $service,
            'action' => $action,
            'response' => $this->sanitizeData($response),
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => now()->toIso8601String(),
            'type' => 'response'
        ]);

        $this->info('Sabre API Response', $logContext);
    }

    public function logAuth(string $type, string $action, ?array $details = null, array $context = []): void
    {
        $logContext = array_merge($this->context, $context, [
            'auth_type' => $type,
            'action' => $action,
            'details' => $this->sanitizeData($details ?? []),
            'timestamp' => now()->toIso8601String(),
            'type' => 'auth'
        ]);

        $this->info('Sabre Authentication', $logContext);
    }

    public function logError(\Throwable $e, array $context = []): void
    {
        $logContext = array_merge($this->context, $context, [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => now()->toIso8601String(),
            'type' => 'error'
        ]);

        $this->error('Sabre API Error', $logContext);
    }

    public function setContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    public function clearContext(): self
    {
        $this->context = [];
        return $this;
    }

    private function sanitizeData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'token',
            'access_token',
            'refresh_token',
            'secret',
            'api_key',
            'cardNumber',
            'cvv',
            'securityCode'
        ];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveFields) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $value = '********';
            }
        });

        return $data;
    }

    // PSR-3 LoggerInterface implementation
    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $this->enrichContext($context));
    }

    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $this->enrichContext($context));
    }

    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $this->enrichContext($context));
    }

    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $this->enrichContext($context));
    }

    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $this->enrichContext($context));
    }

    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $this->enrichContext($context));
    }

    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $this->enrichContext($context));
    }

    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $this->enrichContext($context));
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $this->enrichContext($context));
    }

    private function enrichContext(array $context): array
    {
        return array_merge(
            $this->context,
            [
                'environment' => config('sabre.environment'),
                'request_id' => request()->header('X-Request-ID') ?? uniqid(),
                'timestamp' => now()->toIso8601String()
            ],
            $context
        );
    }
}
