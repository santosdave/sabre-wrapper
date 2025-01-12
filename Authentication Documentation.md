# Sabre Authentication Documentation

## Table of Contents

1. [Overview](#overview)
2. [Authentication Methods](#authentication-methods)
3. [Service-Based Authentication](#service-based-authentication)
4. [Configuration](#configuration)
5. [Usage Examples](#usage-examples)
6. [Error Handling](#error-handling)
7. [Best Practices](#best-practices)
8. [Monitoring & Maintenance](#monitoring--maintenance)

## Overview

The Sabre wrapper provides three distinct authentication mechanisms to interact with Sabre APIs:

- REST OAuth Token Authentication
- SOAP Session Authentication
- SOAP Stateless Token Authentication

Each authentication method is designed for specific use cases and services, with automatic token management and session handling.

## Authentication Methods

### 1. REST OAuth Token Authentication

Used for modern REST API endpoints with token-based authentication.

**Characteristics:**

- Token Lifetime: 7 days
- Auto-refresh: 5 minutes before expiry
- Format: Bearer token
- Stateless operation

**Implementation:**

```php
$token = $this->auth->getToken('rest');

$response = $client->request('POST', '/v4.3.0/shop/flights', [
    'headers' => [
        'Authorization' => "Bearer {$token}"
    ]
]);
```

### 2. SOAP Session Authentication

Manages session-based authentication for SOAP services requiring state maintenance.

**Characteristics:**

- Session Lifetime: 15 minutes
- Pool Size: Maximum 5 concurrent sessions
- Auto-refresh: At 14 minutes
- Requires session release

**Implementation:**

```php
try {
    $session = $this->auth->getToken('soap_session');

    $response = $client->send('CreatePNRRQ', [
        'SessionToken' => $session,
        'data' => $requestData
    ]);
} finally {
    $this->auth->releaseSession($session);
}
```

### 3. SOAP Stateless Token Authentication

Provides stateless authentication for SOAP services not requiring session management.

**Characteristics:**

- Token Lifetime: 7 days
- No session management
- Binary security token format
- Suitable for low-frequency operations

**Implementation:**

```php
$token = $this->auth->getToken('soap_stateless');

$response = $client->send('AvailabilityRQ', [
    'SecurityToken' => $token,
    'data' => $requestData
]);
```

## Service-Based Authentication

Different Sabre services require specific authentication methods. Here's the mapping:

### REST OAuth Services

```php
// Shopping Services
- Bargain Finder Max (BFM)
- Alternate Airport Shop
- Alternate Date Shop

// Order Management
- Create Orders
- View Orders
- Modify Orders

// Pricing Services
- Price Offers
- Revalidate Prices

// Ancillary Services
- Seats
- Meals
- Baggage
```

### SOAP Session Services

```php
// Booking Services
- Create PNR
- Modify PNR
- Cancel PNR

// Queue Management
- Queue Place
- Queue Remove
- Queue List

// Ticketing Services
- Issue Tickets
- Void Tickets
- Exchange Tickets
```

### SOAP Stateless Services

```php
// Reference Services
- City Pairs
- Airport Info
- Airline Info

// Schedule Services
- Availability Check
- Schedule Search
- Route Info
```

## Configuration

```php
// config/sabre.php
return [
    'auth' => [
        'token_lifetime' => [
            'rest' => env('SABRE_REST_TOKEN_LIFETIME', 604800),
            'soap_session' => env('SABRE_SOAP_SESSION_LIFETIME', 900),
            'soap_stateless' => env('SABRE_SOAP_STATELESS_LIFETIME', 604800)
        ],

        'session_pool' => [
            'enabled' => true,
            'size' => 5,
            'cleanup_interval' => 900
        ],

        'retry' => [
            'max_attempts' => 3,
            'delay' => 1000,
            'multiplier' => 2
        ]
    ]
];
```

## Usage Examples

### 1. Shopping Service

```php
class ShoppingService extends BaseRestService
{
    public function searchFlights(SearchRequest $request): SearchResponse
    {
        // Uses REST OAuth
        return $this->client->post('/v3/offers/shop', [
            'authenticate' => true, // Triggers automatic token handling
            'json' => $request->toArray()
        ]);
    }
}
```

### 2. Booking Service

```php
class BookingService extends BaseSoapService
{
    public function createPNR(BookingRequest $request): string
    {
        // Uses SOAP Session
        return $this->withSession(function($session) use ($request) {
            return $this->client->send('CreatePNRRQ', [
                'session' => $session,
                'data' => $request->toArray()
            ]);
        });
    }
}
```

### 3. Reference Service

```php
class ReferenceService extends BaseSoapService
{
    public function getCityPairs(): array
    {
        // Uses SOAP Stateless
        return $this->client->send('CityPairRQ', [
            'authenticate' => true // Triggers automatic token handling
        ]);
    }
}
```

## Error Handling

The wrapper provides comprehensive error handling for authentication-related issues:

```php
try {
    $token = $this->auth->getToken($type);
} catch (SabreAuthenticationException $e) {
    // Handle authentication failure
    $this->logger->error('Authentication failed', [
        'type' => $type,
        'error' => $e->getMessage()
    ]);
    throw $e;
} catch (SabreRateLimitException $e) {
    // Handle rate limiting
    $this->logger->warning('Rate limit exceeded', [
        'retry_after' => $e->getRetryAfter()
    ]);
    throw $e;
}
```

## Best Practices

1. **Token Management**

   - Never store tokens in code
   - Always use the authentication service
   - Implement proper token rotation
   - Monitor token expiration

2. **Session Handling**

   - Always release SOAP sessions
   - Use try-finally blocks
   - Monitor session pool size
   - Implement session cleanup

3. **Security**
   - Secure credential storage
   - Regular token rotation
   - Proper rate limiting
   - Monitoring for suspicious activity

## Monitoring & Maintenance

### Health Checks

```php
$stats = $auth->getAuthenticationStats();

// Monitor session pool
$sessionStats = $auth->getSessionPoolStats();
if ($sessionStats['utilization'] > 80) {
    $this->logger->warning('High session pool utilization');
}

// Monitor error rates
$errorStats = $auth->getErrorStats();
if ($errorStats['rate'] > $threshold) {
    $this->logger->alert('High authentication error rate');
}
```

### Maintenance Tasks

```php
// Clean up expired sessions
$auth->cleanupSessions();

// Rotate tokens
$auth->rotateTokens();

// Archive logs
$auth->archiveAuthLogs();
```
