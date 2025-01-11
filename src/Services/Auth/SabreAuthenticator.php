<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Santosdave\SabreWrapper\Contracts\SabreAuthenticatable;
use Santosdave\SabreWrapper\Exceptions\Auth\SabreAuthenticationException;
use Santosdave\SabreWrapper\Models\Auth\TokenRequest;
use Santosdave\SabreWrapper\Models\Auth\TokenResponse;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class SabreAuthenticator implements SabreAuthenticatable
{
    private Client $client;
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

    private function setupClient(): void
    {
        $this->client = new Client([
            'base_uri' => config("sabre.endpoints.{$this->environment}.rest"),
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);
    }

    private function initializeCacheKeys(): void
    {
        $base = "sabre_{$this->username}_{$this->pcc}";
        $this->cacheKeys = [
            'rest' => "{$base}_rest",
            'soap_session' => "{$base}_session",
            'soap_stateless' => "{$base}_stateless"
        ];
    }

    public function getToken(string $type = 'rest'): string
    {
        if ($type === 'soap_session') {
            return $this->sessionPool->getSession();
        }

        if ($token = Cache::get($this->cacheKeys[$type])) {
            // Check if token needs refresh or rotation
            if ($this->tokenManager->shouldRefreshToken($type, $token)) {
                return $this->tokenRotationService->rotateToken($type);
            }
            return $token;
        }

        $this->refreshToken($type);
        return Cache::get($this->cacheKeys[$type]);
    }

    public function refreshToken(string $type = 'rest'): void
    {
        try {
            $method = "refresh{$type}Token";
            $this->$method();
        } catch (\Exception $e) {
            throw new SabreAuthenticationException(
                "Failed to refresh {$type} token: " . $e->getMessage(),
                401,
                $type
            );
        }
    }

    public function validateToken(string $token, string $type = 'rest'): bool
    {
        return $this->tokenRotationService->validateToken($token, $type);
    }


    private function refreshRestToken(): void
    {
        $request = new TokenRequest($this->clientId, $this->clientSecret);
        $request->setCredentials($this->username, $this->password)
            ->setDomain('AA');

        $response = $this->client->post('/v3/auth/token', [
            'form_params' => $request->toArray()
        ]);

        $tokenResponse = new TokenResponse(
            json_decode($response->getBody()->getContents(), true)
        );

        $this->handleTokenResponse($tokenResponse, 'rest');
    }

    private function refreshSoapSessionToken(): void
    {
        // Implementation for SOAP session token
        $response = $this->client->post(config("sabre.endpoints.{$this->environment}.soap"), [
            'headers' => ['Content-Type' => 'text/xml'],
            'body' => $this->buildSessionCreateRequest()
        ]);

        // Parse response and store token
        $this->handleSoapResponse($response, 'soap_session');
    }


    private function refreshSoapStatelessToken(): void
    {
        // Implementation for SOAP stateless token
        $response = $this->client->post(config("sabre.endpoints.{$this->environment}.soap"), [
            'headers' => ['Content-Type' => 'text/xml'],
            'body' => $this->buildTokenCreateRequest()
        ]);

        // Parse response and store token
        $this->handleSoapResponse($response, 'soap_stateless');
    }

    private function handleTokenResponse(TokenResponse $response, string $type): void
    {
        if (!$response->isSuccess()) {
            throw new SabreAuthenticationException(
                implode(', ', $response->getErrors()),
                401,
                $type
            );
        }

        $ttl = $this->getTokenTTL($type);
        Cache::put(
            $this->cacheKeys[$type],
            $response->getAccessToken(),
            now()->addSeconds($ttl)
        );
    }

    private function getTokenTTL(string $type): int
    {
        return config("sabre.auth.token_lifetime.{$type}", 3600) - 300;
    }

    public function isTokenExpired(string $type = 'rest'): bool
    {
        return !Cache::has($this->cacheKeys[$type]);
    }

    public function getAuthorizationHeader(string $type = 'rest'): string
    {
        return 'Bearer ' . $this->getToken($type);
    }

    private function buildSessionCreateRequest(): string
    {
        // Build SOAP envelope for SessionCreateRQ
        return ''; // Implement using XMLBuilder
    }

    private function buildTokenCreateRequest(): string
    {
        // Build SOAP envelope for TokenCreateRQ
        return ''; // Implement using XMLBuilder
    }

    private function handleSoapResponse($response, string $type): void
    {
        // Parse SOAP response and extract token
        // Store token in cache
    }
}
