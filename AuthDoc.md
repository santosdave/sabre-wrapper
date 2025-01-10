# Sabre Authentication & Token Management Guide

## Overview

The Sabre wrapper implements three authentication methods that mirror Sabre's official authentication mechanisms:

1. REST OAuth Token (Session-less)
2. SOAP Token (Session-less)
3. SOAP Session (Session-based)

### Authentication Flow Diagram

```
                                Client Request
                                      ↓
                                Token Type?
                                      ↓
          ┌──────────────────────────┴─────────────────────┐
          ↓                          ↓                      ↓
    REST OAuth                 Session Token          SOAP Token
          ↓                          ↓                      ↓
   Token Valid?               Session Valid?         Token Valid?
          ↓                          ↓                      ↓
    ┌────┴────┐               ┌──────┴─────┐         ┌─────┴────┐
    ↓         ↓               ↓            ↓         ↓          ↓
   Yes        No             Yes           No       Yes         No
    ↓         ↓               ↓            ↓         ↓          ↓
 Use      Get New        Use Session   Create New   Use     Get New
Token  REST Token        from Pool     Session    Token   SOAP Token
                                          ↓
                                    Add to Pool
```

## Implementation Details

### 1. Base Authentication Handler

```php
class SabreAuthenticator implements SabreAuthenticatable
{
    private SessionPool $sessionPool;
    private TokenRefreshManager $tokenManager;
    private array $cacheKeys;

    public function __construct(
        private string $username,
        private string $password,
        private string $pcc,
        private string $environment,
        private string $clientId,
        private string $clientSecret
    ) {
        $this->sessionPool = new SessionPool($this);
        $this->tokenManager = new TokenRefreshManager($this);
        $this->initializeCacheKeys();
    }

    public function getToken(string $type = 'rest'): string
    {
        // Handle SOAP sessions separately through pool
        if ($type === 'soap_session') {
            return $this->sessionPool->getSession();
        }

        // Check cache for existing token
        if ($token = Cache::get($this->cacheKeys[$type])) {
            if (!$this->tokenManager->shouldRefreshToken($type, $token)) {
                return $token;
            }
        }

        // Get new token
        return $this->refreshToken($type);
    }

    public function refreshToken(string $type = 'rest'): string
    {
        $response = match ($type) {
            'rest' => $this->getRestToken(),
            'soap_session' => $this->createSoapSession(),
            'soap_stateless' => $this->createSoapToken(),
            default => throw new InvalidArgumentException("Invalid token type: {$type}")
        };

        $this->tokenManager->storeTokenData($type, $response);
        return $response;
    }
}
```

### 2. REST OAuth Implementation

```php
private function getRestToken(): string
{
    try {
        $client = new Client([
            'base_uri' => config("sabre.endpoints.{$this->environment}.rest")
        ]);

        $response = $client->post('/v3/auth/token', [
            'headers' => [
                'Authorization' => 'Basic ' . $this->getBasicAuthString(),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'grant_type' => 'password',
                'username' => "{$this->username}-{$this->pcc}-AA",
                'password' => $this->password
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['access_token'];
    } catch (\Exception $e) {
        throw new SabreAuthenticationException(
            "REST authentication failed: " . $e->getMessage(),
            401
        );
    }
}

private function getBasicAuthString(): string
{
    return base64_encode("{$this->clientId}:{$this->clientSecret}");
}
```

### 3. SOAP Session Management

```php
class SessionPool
{
    private const MAX_SESSIONS = 5;
    private array $sessions = [];

    public function getSession(): string
    {
        // Try to get available session
        $session = $this->getAvailableSession();
        if ($session) {
            return $session;
        }

        // Create new session if pool not full
        if (count($this->sessions) < self::MAX_SESSIONS) {
            return $this->createNewSession();
        }

        // Wait for available session
        return $this->waitForSession();
    }

    private function getAvailableSession(): ?string
    {
        foreach ($this->sessions as $key => $session) {
            if (!$session['in_use'] && !$this->isExpired($session)) {
                return $this->markSessionInUse($key);
            }
        }
        return null;
    }

    private function createNewSession(): string
    {
        $xmlBuilder = new XMLBuilder();
        $request = $xmlBuilder->buildSessionCreateRequest([
            'username' => $this->username,
            'password' => $this->password,
            'pcc' => $this->pcc,
            'domain' => 'DEFAULT'
        ]);

        // Send request to Sabre...
        // Store session in pool...
        return $sessionToken;
    }
}
```

## Usage Scenarios

### Scenario 1: REST API Call

```php
// Making a REST API call
public function searchFlights(SearchRequest $request): SearchResponse
{
    try {
        $token = $this->authenticator->getToken('rest');

        $response = $this->client->post('/v4.3.0/shop/flights', [
            'headers' => [
                'Authorization' => "Bearer {$token}"
            ],
            'json' => $request->toArray()
        ]);

        return new SearchResponse($response);
    } catch (SabreAuthenticationException $e) {
        // Handle authentication failure
    }
}
```

### Scenario 2: SOAP Session Management

```php
// Using SOAP session
public function createPNR(BookingRequest $request): string
{
    try {
        $session = $this->authenticator->getToken('soap_session');

        // Build SOAP request with XMLBuilder
        $xml = $this->xmlBuilder->buildCreatePNRRequest($session, $request);

        $response = $this->soapClient->send($xml);

        // Release session back to pool
        $this->sessionPool->releaseSession($session);

        return $response['pnr'];
    } catch (SabreApiException $e) {
        // Handle API error
    } finally {
        // Ensure session is released even on error
        if (isset($session)) {
            $this->sessionPool->releaseSession($session);
        }
    }
}
```

### Scenario 3: Token Refresh

```php
// Token refresh handling
public function executeWithRetry(callable $operation)
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

            // Force token refresh
            $this->tokenManager->clearTokenCache();

            // Wait before retry
            $delay = $this->calculateRetryDelay($attempts);
            sleep($delay);
        }
    }
}
```

## Alignment with Sabre's Requirements

This implementation aligns closely with Sabre's authentication requirements:

1. REST OAuth Token:

   - 7-day expiration
   - Base64 encoded credentials
   - Proper grant type and format

2. SOAP Session:

   - 15-minute session lifetime
   - Session pool management
   - Automatic refresh at 14 minutes
   - Proper session cleanup

3. SOAP Stateless Token:
   - 7-day expiration
   - Reusable across requests
   - Binary security token format

## Best Practices Implemented

1. Token Caching:

   - Cached tokens with proper TTL
   - Automatic refresh before expiration
   - Cache invalidation on errors

2. Session Pool:

   - Limited pool size
   - Session reuse
   - Proper cleanup
   - Lock management

3. Error Handling:

   - Specific error types
   - Retry mechanisms
   - Rate limiting support
   - Proper error propagation

4. Configuration:
   - Environment-specific settings
   - Configurable timeouts
   - Adjustable pool sizes
   - Custom retry policies
