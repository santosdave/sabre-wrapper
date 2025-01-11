# Sabre API Wrapper Documentation

## Overview

A comprehensive Laravel wrapper for Sabre APIs, providing seamless integration with both REST and SOAP endpoints. The wrapper handles authentication management, rate limiting, distributed locking, and error handling, with full support for NDC (New Distribution Capability) workflows.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Authentication](#authentication)
4. [Service Usage](#service-usage)
5. [NDC Implementation](#ndc-implementation)
6. [Error Handling](#error-handling)
7. [Advanced Features](#advanced-features)
8. [Best Practices](#best-practices)

## Installation

```bash
composer require santosdave/sabre-wrapper
```

The service provider will auto-register in Laravel 8+. For earlier versions, add manually:

```php
// config/app.php
'providers' => [
    Santosdave\SabreWrapper\SabreServiceProvider::class,
],

'aliases' => [
    'Sabre' => Santosdave\SabreWrapper\Facades\Sabre::class,
]
```

Publish configuration:

```bash
php artisan vendor:publish --provider="Santosdave\SabreWrapper\SabreServiceProvider"
```

## Configuration

### Environment Variables

```env
# Required Credentials
SABRE_USERNAME=
SABRE_PASSWORD=
SABRE_PCC=
SABRE_CLIENT_ID=
SABRE_CLIENT_SECRET=

# Environment
SABRE_ENVIRONMENT=cert

# Authentication Settings
SABRE_AUTH_VERSION=3
SABRE_AUTH_METHOD=rest

# Token Lifetimes (seconds)
SABRE_REST_TOKEN_LIFETIME=604800
SABRE_SOAP_SESSION_LIFETIME=900
SABRE_SOAP_STATELESS_LIFETIME=604800

# Session Pool Settings
SABRE_SESSION_POOL_ENABLED=true
SABRE_SESSION_POOL_SIZE=5
SABRE_SESSION_POOL_CLEANUP_INTERVAL=900

# Rate Limiting
SABRE_RATE_LIMITING_ENABLED=true
SABRE_RATE_LIMITING_DEFAULT_LIMIT=100
SABRE_RATE_LIMITING_WINDOW=60
```

## Service Usage

### Shopping Service

```php
use Santosdave\SabreWrapper\Facades\Sabre;

// Create shopping request
$request = new BargainFinderMaxRequest();
$request->addOriginDestination(
    'JFK',
    'LHR',
    '2024-03-15'
)
->addTraveler('ADT', 1)
->setTravelPreferences([
    'vendorPrefs' => ['AA', 'BA'],
    'cabinPrefs' => ['Y']
]);

$results = Sabre::shopping()->bargainFinderMax($request);

// Access results
foreach ($results->getOffers() as $offer) {
    echo $offer['total_fare']['amount'];
}
```

### Order Management

```php
// Create order
$request = new OrderCreateRequest();
$request->setOffer(
    $priceResponse->getOfferId(),
    [$priceResponse->getOfferItemId()]
)
->addPassenger(
    'PAX1',
    'ADT',
    'John',
    'Doe',
    '1990-01-01'
)
->addContactInfo(
    'CI-1',
    ['john@example.com'],
    ['1234567890']
);

$order = Sabre::order()->createOrder($request);

// Fulfill order
$fulfillRequest = new OrderFulfillRequest($order->getOrderId());
$fulfillRequest->setPaymentCard(
    $cardNumber,
    $expirationDate,
    $vendorCode,
    $cvv,
    'CI-1'
)
->setAmount(161.60, 'USD');

$fulfilled = Sabre::order()->fulfillOrder($fulfillRequest);
```

### Seat Service

```php
// Get seat map
$request = new SeatMapRequest();
$seatMap = Sabre::seat()->getSeatMap($request);

// Assign seats
$seatRequest = new SeatAssignRequest($orderId);
$seatRequest
    ->addSeatAssignment(
        'PAX1',
        'SEG1',
        '12A',
        ['window' => true]
    )
    ->setPaymentCard(
        $cardNumber,
        $expirationDate,
        $cardCode,
        $cardType,
        50.00,
        'USD'
    );

$seatResponse = Sabre::seat()->assignSeats($seatRequest);
```

## Error Handling

The wrapper provides specific exception types:

```php
try {
    $response = Sabre::booking()->createBooking($request);
} catch (SabreAuthenticationException $e) {
    // Handle authentication errors
    Log::error('Authentication failed', [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (SabreRateLimitException $e) {
    // Handle rate limiting
    Log::warning('Rate limit exceeded', [
        'retry_after' => $e->getRetryAfter(),
        'reset' => $e->getReset()
    ]);
} catch (SabreApiException $e) {
    // Handle API errors
    Log::error('API error', [
        'message' => $e->getMessage(),
        'details' => $e->getErrorDetails()
    ]);
}
```

## Advanced Features

### Health Monitoring

```php
// Get service health status
$health = Sabre::health()->checkStatus();

// Get detailed metrics
$metrics = Sabre::health()->getMetrics();

// Monitor specific services
$metrics = [
    'shopping' => $health->getServiceMetrics('shopping'),
    'booking' => $health->getServiceMetrics('booking'),
    'availability' => $health->getServiceMetrics('availability')
];
```

### Rate Limiting

```php
// Service-specific limits
'limits' => [
    'shopping' => [
        'bargain_finder_max' => ['limit' => 50, 'window' => 60],
        'alternative_dates' => ['limit' => 30, 'window' => 60],
        'insta_flights' => ['limit' => 40, 'window' => 60]
    ],
    'booking' => [
        'create' => ['limit' => 20, 'window' => 60],
        'modify' => ['limit' => 30, 'window' => 60],
        'cancel' => ['limit' => 25, 'window' => 60]
    ]
]
```

### Caching Strategy

```php
// Configure cache
'cache' => [
    'enabled' => true,
    'ttl' => [
        'shopping.results' => 300,      // 5 minutes
        'pricing.quotes' => 900,        // 15 minutes
        'reference.data' => 86400       // 24 hours
    ],
    'prefix' => 'sabre_cache_',
    'store' => 'redis'
]
```

### Session Pool Management

```php
// Configure session pool
'session_pool' => [
    'enabled' => true,
    'size' => 5,
    'cleanup_interval' => 900,
    'lock_timeout' => 10
]
```

## Best Practices

1. Error Handling

   - Implement proper exception handling
   - Log errors appropriately
   - Use retry mechanisms for transient failures

2. Rate Limiting

   - Configure appropriate limits
   - Implement backoff strategies
   - Monitor rate limit usage

3. Caching

   - Cache frequently accessed data
   - Implement proper invalidation
   - Use appropriate TTLs

4. Session Management

   - Configure appropriate pool size
   - Implement proper cleanup
   - Handle concurrent access

5. Monitoring
   - Monitor service health
   - Track error rates
   - Monitor performance metrics
