# Sabre Logger Documentation

## Table of Contents

1. [Overview](#overview)
2. [Configuration](#configuration)
3. [Core Features](#core-features)
4. [Usage Examples](#usage-examples)
5. [Log Types](#log-types)
6. [Integration Points](#integration-points)
7. [Monitoring & Alerts](#monitoring--alerts)
8. [Best Practices](#best-practices)

## Overview

The Sabre Logger provides comprehensive logging capabilities for all Sabre API interactions, including requests, responses, authentication events, and errors. It supports multiple output formats, log levels, and monitoring integrations.

### Key Features

- Request/Response logging
- Authentication event tracking
- Error logging with context
- Performance monitoring
- Sensitive data sanitization
- Log rotation and archiving
- Multiple output formats (JSON, text)
- Monitoring integrations (Slack, Datadog)

## Configuration

```php
// config/sabre/logging.php
return [
    'logging' => [
        'enabled' => env('SABRE_LOGGING_ENABLED', true),
        'channel' => env('SABRE_LOG_CHANNEL', 'sabre'),
        'path' => storage_path('logs/sabre.log'),
        'level' => env('SABRE_LOG_LEVEL', 'debug'),
        'days' => env('SABRE_LOG_DAYS', 7),

        // Separate log files
        'separate_files' => [
            'auth' => true,     // Authentication events
            'requests' => true, // API requests/responses
            'errors' => true    // Error events
        ],

        // Data sanitization
        'sanitize' => [
            'enabled' => true,
            'fields' => [
                'password',
                'token',
                'credit_card',
                'cvv'
            ]
        ],

        // Performance monitoring
        'performance' => [
            'log_slow_requests' => true,
            'slow_request_threshold' => 1000, // milliseconds
            'include_memory_usage' => false
        ],

        // Alerting
        'alerts' => [
            'channels' => ['slack', 'email'],
            'error_threshold' => 10,
            'cooldown_minutes' => 15
        ]
    ]
];
```

## Core Features

### 1. Request/Response Logging

```php
// Automatic logging via middleware
class SabreLoggingMiddleware
{
    public function handle($request, Closure $next)
    {
        // Log request
        $this->logger->logRequest(
            service: 'shopping',
            action: 'search',
            request: $request->all()
        );

        $response = $next($request);

        // Log response
        $this->logger->logResponse(
            service: 'shopping',
            action: 'search',
            response: $response->getData(),
            duration: $this->calculateDuration()
        );

        return $response;
    }
}
```

### 2. Authentication Logging

```php
// Logging authentication events
$this->logger->logAuth(
    type: 'rest',
    action: 'token_refresh',
    details: [
        'username' => $this->username,
        'environment' => $this->environment
    ]
);
```

### 3. Error Logging

```php
try {
    // Operation code
} catch (\Exception $e) {
    $this->logger->logError($e, [
        'service' => 'booking',
        'action' => 'create_pnr',
        'context' => [
            'request_id' => $requestId,
            'pnr' => $pnr
        ]
    ]);
    throw $e;
}
```

### 4. Performance Logging

```php
// Automatic performance tracking
$this->logger->startTiming('operation_name');

// Your code here

$duration = $this->logger->endTiming('operation_name');
if ($duration > $this->slowRequestThreshold) {
    $this->logger->warning('Slow operation detected', [
        'operation' => 'operation_name',
        'duration' => $duration,
        'threshold' => $this->slowRequestThreshold
    ]);
}
```

## Usage Examples

### 1. Basic Logging

```php
class BookingService extends BaseService
{
    public function createPNR(BookingRequest $request): string
    {
        $this->logger->info('Starting PNR creation', [
            'passengers' => count($request->passengers)
        ]);

        try {
            $result = $this->client->createPNR($request);

            $this->logger->info('PNR created successfully', [
                'pnr' => $result->pnr
            ]);

            return $result->pnr;
        } catch (\Exception $e) {
            $this->logger->logError($e, [
                'request' => $request->toArray()
            ]);
            throw $e;
        }
    }
}
```

### 2. Context Logging

```php
class ShoppingService extends BaseService
{
    public function searchFlights(SearchRequest $request): array
    {
        // Set context for all subsequent log entries
        $this->logger->setContext([
            'search_id' => uniqid(),
            'origin' => $request->origin,
            'destination' => $request->destination
        ]);

        try {
            return $this->performSearch($request);
        } finally {
            // Clear context
            $this->logger->clearContext();
        }
    }
}
```

### 3. Performance Monitoring

```php
class PerformanceAwareService
{
    public function longRunningOperation()
    {
        $timer = $this->logger->startTimer();

        // Operation code

        $duration = $timer->stop();
        $this->logger->info('Operation completed', [
            'duration_ms' => $duration,
            'memory_used' => memory_get_peak_usage(true)
        ]);
    }
}
```

## Log Types

### 1. Request Logs

```json
{
  "timestamp": "2024-01-12T10:00:00.000Z",
  "type": "request",
  "service": "shopping",
  "action": "search_flights",
  "request_id": "req_123",
  "data": {
    "origin": "JFK",
    "destination": "LHR",
    "date": "2024-02-01"
  }
}
```

### 2. Response Logs

```json
{
  "timestamp": "2024-01-12T10:00:01.000Z",
  "type": "response",
  "service": "shopping",
  "action": "search_flights",
  "request_id": "req_123",
  "duration_ms": 1234,
  "status": 200,
  "data": {
    "flights": []
  }
}
```

### 3. Error Logs

```json
{
  "timestamp": "2024-01-12T10:00:01.000Z",
  "type": "error",
  "service": "booking",
  "action": "create_pnr",
  "request_id": "req_124",
  "error": {
    "type": "SabreApiException",
    "message": "Invalid passenger data",
    "code": 400,
    "trace": "..."
  }
}
```

## Integration Points

### 1. Middleware Integration

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'sabre' => [
        \Santosdave\SabreWrapper\Http\Middleware\SabreLoggingMiddleware::class,
    ]
];
```

### 2. Service Integration

```php
class BaseService
{
    protected SabreLogger $logger;

    public function __construct(SabreLogger $logger)
    {
        $this->logger = $logger;
        $this->logger->setContext([
            'service' => $this->getServiceName()
        ]);
    }
}
```

### 3. Client Integration

```php
class SabreClient
{
    public function request($method, $url, $options = [])
    {
        $this->logger->logRequest(...);
        $response = $this->sendRequest($method, $url, $options);
        $this->logger->logResponse(...);
        return $response;
    }
}
```

## Monitoring & Alerts

### 1. Alert Configuration

```php
// Slack alerts
$this->logger->configureAlerts([
    'channels' => ['slack'],
    'webhook_url' => env('SLACK_WEBHOOK_URL'),
    'channel' => '#sabre-alerts',
    'threshold' => [
        'errors' => 10,
        'response_time' => 2000
    ]
]);
```

### 2. Health Monitoring

```php
// Regular health checks
$stats = $this->logger->getStats();
if ($stats['error_rate'] > $threshold) {
    $this->logger->alert('High error rate detected', $stats);
}
```

## Best Practices

1. **Log Levels**

   - Use appropriate log levels (DEBUG, INFO, WARNING, ERROR)
   - Be consistent with log level usage
   - Don't overuse ERROR level

2. **Context**

   - Always include request ID
   - Add relevant business context
   - Don't log sensitive data

3. **Performance**

   - Use batch logging when possible
   - Monitor log file sizes
   - Implement log rotation

4. **Error Handling**

   - Log errors with full context
   - Include stack traces
   - Add correlation IDs

5. **Maintenance**
   - Regular log cleanup
   - Monitor disk usage
   - Archive old logs
