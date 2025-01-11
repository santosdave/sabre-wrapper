# Sabre Authentication & Token Management Guide

## Overview

The Sabre wrapper implements a comprehensive authentication system with three primary methods:

1. REST OAuth Token (Session-less)
2. SOAP Token (Session-less)
3. SOAP Session (Session-based)

Each method is handled by specialized components:

- `SabreAuthenticator`: Core authentication management
- `TokenRotationService`: Token rotation and validation
- `TokenRefreshManager`: Token refresh scheduling
- `SessionPool`: SOAP session management
- `DistributedLockService`: Concurrent access management

## Implementation Details

### Authentication Service

```php
class SabreAuthenticator implements SabreAuthenticatable
{
    private SessionPool $sessionPool;
    private TokenRefreshManager $tokenManager;
    private TokenRotationService $tokenRotationService;
    private array $cacheKeys = [];

    public function __construct(
        private string $username,
        private string $password,
        private string $pcc,
        private string $environment,
        private string $clientId,
        private string $clientSecret
    ) {
        $this->setupClient();
        $this->initializeCacheKeys();

        $this->sessionPool = new SessionPool($this);
        $this->tokenManager = new TokenRefreshManager($this);
        $this->tokenRotationService = new TokenRotationService(
            $this->tokenManager,
            $this
        );
    }

    public function getToken(string $type = 'rest'): string
    {
        if ($type === 'soap_session') {
            return $this->sessionPool->getSession();
        }

        if ($token = Cache::get($this->cacheKeys[$type])) {
            if ($this->tokenManager->shouldRefreshToken($type, $token)) {
                return $this->tokenRotationService->rotateToken($type);
            }
            return $token;
        }

        $this->refreshToken($type);
        return Cache::get($this->cacheKeys[$type]);
    }
}
```

### Token Rotation Service

```php
class TokenRotationService
{
    private const TOKEN_ROTATION_KEY = 'sabre_token_rotation_';
    private const MAX_TOKENS = 2; // Keep current and previous token

    public function rotateToken(string $type = 'rest'): string
    {
        try {
            // Get current token before creating new one
            $currentToken = $this->authenticator->getToken($type);

            // Create new token
            $this->authenticator->refreshToken($type);
            $newToken = $this->authenticator->getToken($type);

            // Store both tokens
            $this->storeRotatedTokens($type, $currentToken, $newToken);

            Log::info('Token rotated successfully', [
                'type' => $type,
                'token_prefix' => substr($newToken, 0, 10)
            ]);

            return $newToken;
        } catch (\Exception $e) {
            Log::error('Token rotation failed', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
```

### Session Pool Management

```php
class SessionPool
{
    private const POOL_SIZE = 5;
    private const SESSION_PREFIX = 'sabre_session_';
    private const POOL_KEY = 'sabre_session_pool';
    private const LOCK_KEY = 'sabre_session_lock';
    private const LOCK_TTL = 10;

    public function getSession(): string
    {
        // Try to get an existing session
        $session = $this->getAvailableSession();

        if ($session) {
            return $session;
        }

        // Create new session if pool isn't full
        return $this->createNewSession();
    }

    private function shouldRefreshSession(array $session): bool
    {
        $lastRefresh = $session['last_refreshed'] ?? $session['created_at'];
        return (time() - $lastRefresh) > (14 * 60); // 14 minutes
    }
}
```

## Configuration

### Token Lifetimes

```php
'auth' => [
    'token_lifetime' => [
        'rest' => env('SABRE_REST_TOKEN_LIFETIME', 604800),     // 7 days
        'soap_session' => env('SABRE_SOAP_SESSION_LIFETIME', 900), // 15 minutes
        'soap_stateless' => env('SABRE_SOAP_STATELESS_LIFETIME', 604800)
    ],
    'refresh_thresholds' => [
        'rest' => env('SABRE_REST_REFRESH_THRESHOLD', 300),     // 5 minutes
        'soap_session' => env('SABRE_SOAP_SESSION_REFRESH_THRESHOLD', 60),
        'soap_stateless' => env('SABRE_SOAP_STATELESS_REFRESH_THRESHOLD', 3600)
    ]
]
```

## Authentication Flows

### 1. REST OAuth Flow

```php
// Get REST token
$token = Sabre::getToken('rest');

// Token automatically refreshes when needed
$service->makeRequest([
    'headers' => [
        'Authorization' => "Bearer {$token}"
    ]
]);
```

### 2. SOAP Session Flow

```php
// Get session from pool
$session = Sabre::getToken('soap_session');

try {
    // Use session
    $result = $service->makeRequest($session);
} finally {
    // Release session back to pool
    $this->sessionPool->releaseSession($session);
}
```

### 3. SOAP Stateless Flow

```php
// Get stateless token
$token = Sabre::getToken('soap_stateless');

// Token automatically refreshes when needed
$service->makeRequest([
    'headers' => [
        'Security' => $token
    ]
]);
```

## Error Handling

```php
try {
    $token = $authenticator->getToken();
} catch (SabreAuthenticationException $e) {
    // Handle authentication failure
    Log::error('Authentication failed', [
        'error' => $e->getMessage(),
        'type' => $e->getTokenType()
    ]);
} catch (SabreRateLimitException $e) {
    // Handle rate limiting
    Log::warning('Rate limit exceeded', [
        'retry_after' => $e->getRetryAfter()
    ]);
}
```

## Best Practices

1. Token Management

   - Use token rotation to prevent service interruption
   - Monitor token expiration
   - Implement proper error handling

2. Session Pool

   - Configure appropriate pool size
   - Implement session cleanup
   - Handle concurrent access

3. Security

   - Store credentials securely
   - Rotate tokens regularly
   - Monitor for suspicious activity

4. Performance
   - Use session pooling effectively
   - Implement proper caching
   - Handle rate limits

## Rate Limiting

Authentication requests are subject to rate limiting:

```php
'limits' => [
    'authentication' => [
        'token_create' => ['limit' => 10, 'window' => 60],
        'session_create' => ['limit' => 5, 'window' => 60]
    ]
]
```

## Monitoring

```php
// Monitor token health
$metrics = [
    'tokens' => [
        'rest' => $authenticator->getTokenMetrics('rest'),
        'soap_session' => $sessionPool->getMetrics(),
        'soap_stateless' => $authenticator->getTokenMetrics('soap_stateless')
    ],
    'rate_limits' => $rateLimiter->getMetrics('authentication'),
    'errors' => $this->getErrorMetrics()
];
```

Would you like me to:

1. Add more error handling scenarios?
2. Include additional configuration examples?
3. Add more authentication workflows?
4. Cover any specific aspect in more detail?
