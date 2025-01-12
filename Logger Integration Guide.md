# Sabre Logger Integration Guide

## Quick Start

### 1. Installation
```bash
# Register service provider in config/app.php
'providers' => [
    Santosdave\SabreWrapper\Providers\SabreLoggingServiceProvider::class,
]

# Publish configuration
php artisan vendor:publish --tag=sabre-logging-config
```

### 2. Basic Usage
```php
class YourService
{
    public function __construct(private SabreLogger $logger) {}

    public function someOperation()
    {
        // Basic logging
        $this->logger->info('Operation started');

        // With context
        $this->logger->info('Processing request', [
            'context' => 'additional info'
        ]);

        // Error logging
        try {
            // Your code
        } catch (\Exception $e) {
            $this->logger->logError($e);
            throw $e;
        }
    }
}
```

### 3. Middleware Setup
```php
// In RouteServiceProvider
Route::middleware(['sabre.logging'])->group(function () {
    // Your routes
});
```

### 4. Error Handler Integration
```php
// In App\Exceptions\Handler
public function register(): void
{
    $this->reportable(function (SabreApiException $e) {
        app(SabreLogger::class)->logError($e);
    });
}
```

## Common Use Cases

### 1. API Request Logging
```php
$this->logger->logRequest(
    service: 'shopping',
    action: 'search',
    request: $data,
    context: [
        'user_id' => $userId,
        'session_id' => $sessionId
    ]
);
```

### 2. Response Logging
```php
$this->logger->logResponse(
    service: 'shopping',
    action: 'search',
    response: $response,
    duration: $duration,
    context: [
        'status' => $response->status()
    ]
);
```

### 3. Performance Monitoring
```php
$timer = $this->logger->startTimer();

// Your code

$duration = $timer->stop();
if ($duration > 1000) { // 1 second
    $this->logger->warning('Slow operation', [
        'duration_ms' => $duration
    ]);
}
```

### 4. Context Management
```php
// Set context for a group of operations
$this->logger->setContext([
    'request_id' => $requestId,
    'operation' => 'booking'
]);

try {
    // Your operations
} finally {
    $this->logger->clearContext();
}
```

## Troubleshooting

### Common Issues

1. **Logs Not Appearing**
```php
// Check configuration
$config = config('sabre.logging');
if (!$config['enabled']) {
    // Logging is disabled
}

// Check permissions
if (!is_writable($config['path'])) {
    // Log file not writable
}
```

2. **Performance Issues**
```php
// Use batch logging
$this->logger->batch()
    ->info('Step 1')
    ->info('Step 2')
    ->error('Error occurred')
    ->flush();

// Monitor log size
$stats = $this->logger->getStats();
if ($stats['file_size'] > $threshold) {
    // Trigger cleanup
}
```

3. **Missing Context**
```php
// Always include base context
$this->logger->setBaseContext([
    'environment' => app()->environment(),
    'application' => 'sabre-wrapper'
]);

// Add request context in middleware
$this->logger->addContext([
    'request_id' => request()->id(),
    'user_agent' => request()->userAgent()
]);
```

## Best Practices Summary

1. **Always include context**
```php
// Good
$this->logger->info('User logged in', ['user_id' => $userId]);

// Bad
$this->logger->info('User logged in');
```

2. **Use appropriate log levels**
```php
// Debug: Detailed information for debugging
$this->logger->debug('SQL query executed', ['query' => $sql]);

// Info: Notable but normal events
$this->logger->info('Order created', ['order_id' => $orderId]);

// Warning: Unusual but not error conditions
$this->logger->warning('High API latency detected');

// Error: Error conditions
$this->logger->error('Payment failed', ['order_id' => $orderId]);
```

3. **Clean up resources**
```php
try {
    $this->logger->setContext([...]);
    // Operations
} finally {
    $this->logger->clearContext();
}
```

4. **Monitor and maintain**
```php
// Regular cleanup
$this->logger->cleanup();

// Monitor health
$health = $this->logger->checkHealth();
if (!$health['healthy']) {
    // Alert operations team
}
```

For more detailed information, please refer to the main documentation.