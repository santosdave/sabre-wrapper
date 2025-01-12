<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use Santosdave\SabreWrapper\Exceptions\Auth\SabreAuthenticationException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Santosdave\SabreWrapper\Contracts\Auth\TokenManagerInterface;
use Santosdave\SabreWrapper\Http\Soap\XMLBuilder;
use Santosdave\SabreWrapper\Services\Logging\SabreLogger;

class SabreAuthenticator implements TokenManagerInterface
{
    private TokenRotator $tokenRotator;
    private SessionManager $sessionManager;
    private DistributedLockService $lockService;
    private AuthenticationRetryHandler $retryHandler;
    private SabreLogger $logger;
    private array $cacheKeys;

    private XMLBuilder $xmlBuilder;

    public function __construct(
        private string $username,
        private string $password,
        private string $pcc,
        private string $environment,
        private string $clientId,
        private string $clientSecret,
        ?SabreLogger $logger = null
    ) {
        $this->tokenRotator = new TokenRotator();
        $this->lockService = new DistributedLockService();
        $this->sessionManager = new SessionManager($this->lockService);
        $this->retryHandler = new AuthenticationRetryHandler();
        $this->xmlBuilder = new XMLBuilder();
        $this->logger = $logger ?? app(SabreLogger::class);
        $this->initializeCacheKeys();
    }

    public function getToken(string $type = 'rest'): string
    {
        return $this->retryHandler->executeWithRetry(function () use ($type) {
            $this->logger->logAuth($type, 'get_token', [
                'environment' => $this->environment,
                'username' => $this->username,
                'pcc' => $this->pcc
            ]);

            try {
                if ($type === 'rest') {
                    return $this->getRestToken();
                } elseif ($type === 'soap_session') {
                    return $this->sessionManager->getCurrentSession();
                } elseif ($type === 'soap_stateless') {
                    return $this->createSoapToken();
                } else {
                    throw new \InvalidArgumentException("Invalid token type: {$type}");
                }
            } catch (\Exception $e) {
                $this->logger->logError($e, [
                    'auth_type' => $type,
                    'action' => 'get_token'
                ]);
                throw $e;
            }
        }, $type);
    }

    private function createSoapToken(): string
    {
        try {
            $client = new \SoapClient(null, [
                'location' => config("sabre.endpoints.{$this->environment}.soap"),
                'uri' => 'http://schemas.xmlsoap.org/soap/envelope/',
                'trace' => true
            ]);

            $request = $this->xmlBuilder->buildTokenCreateRequest([
                'username' => $this->username,
                'password' => $this->password,
                'pcc' => $this->pcc,
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
            ]);

            $response = $client->__doRequest(
                $request,
                config("sabre.endpoints.{$this->environment}.soap"),
                'TokenCreateRQ',
                SOAP_1_1
            );

            // Parse binary security token from response
            $xml = new \SimpleXMLElement($response);
            $token = (string)$xml->xpath('//wsse:BinarySecurityToken')[0];

            if (empty($token)) {
                throw new SabreAuthenticationException('Invalid SOAP token response');
            }

            return $token;
        } catch (\Exception $e) {
            throw new SabreAuthenticationException(
                "SOAP token creation failed: " . $e->getMessage(),
                401
            );
        }
    }

    public function refreshToken(string $type = 'rest'): void
    {
        $this->lockService->withLock("refresh_token_{$type}", function () use ($type) {
            $currentToken = Cache::get($this->cacheKeys[$type]);
            $startTime = microtime(true);

            try {
                switch ($type) {
                    case 'rest':
                        $newToken = $this->requestNewRestToken();
                        break;
                    case 'soap_session':
                        $newToken = $this->createNewSoapSession();
                        break;
                    case 'soap_stateless':
                        $newToken = $this->createSoapToken();
                        break;
                    default:
                        throw new \InvalidArgumentException("Invalid token type: {$type}");
                }

                if ($currentToken) {
                    $this->tokenRotator->rotateToken($type, $currentToken, $newToken);
                }

                $this->cacheToken($type, $newToken);

                $duration = microtime(true) - $startTime;
                $this->logger->logAuth($type, 'token_refreshed', [
                    'duration_ms' => round($duration * 1000, 2)
                ]);
            } catch (\Exception $e) {
                $this->logger->logError($e, [
                    'auth_type' => $type,
                    'action' => 'refresh_token'
                ]);
                throw $e;
            }
        });
    }

    private function createNewSoapSession(): string
    {
        // Implementation for SOAP session creation
        throw new \RuntimeException('Soap session creation not implemented');
    }

    public function isTokenExpired(string $type = 'rest'): bool
    {
        $token = Cache::get($this->cacheKeys[$type]);
        if (!$token) {
            return true;
        }

        $threshold = config("sabre.auth.refresh_thresholds.{$type}", 300);
        $tokenData = Cache::get("token_data_{$type}_{$token}");

        return !$tokenData || ($tokenData['expires_at'] - time()) <= $threshold;
    }

    public function getAuthorizationHeader(string $type = 'rest'): string
    {
        $token = $this->getToken($type);
        return "Bearer {$token}";
    }

    private function getRestToken(): string
    {
        $cacheKey = $this->cacheKeys['rest'];

        if ($token = Cache::get($cacheKey)) {
            if ($this->tokenRotator->isValidToken('rest', $token) && !$this->isTokenExpired('rest')) {
                return $token;
            }
        }

        $this->refreshToken('rest');
        return Cache::get($cacheKey);
    }

    private function requestNewRestToken(): string
    {
        try {
            $client = new Client([
                'base_uri' => config("sabre.endpoints.{$this->environment}.rest")
            ]);

            $response = $client->post('/v3/auth/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'grant_type' => 'password',
                    'username' => "{$this->username}-{$this->pcc}-AA",
                    'password' => $this->password
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (!isset($data['access_token'])) {
                throw new SabreAuthenticationException('Invalid token response');
            }

            return $data['access_token'];
        } catch (\Exception $e) {
            Log::error('REST token request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new SabreAuthenticationException(
                "Failed to obtain REST token: {$e->getMessage()}",
                401,
                'rest'
            );
        }
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

    private function cacheToken(string $type, string $token): void
    {
        $lifetime = config("sabre.auth.token_lifetime.{$type}");
        Cache::put($this->cacheKeys[$type], $token, now()->addSeconds($lifetime));

        Cache::put(
            "token_data_{$type}_{$token}",
            [
                'created_at' => time(),
                'expires_at' => time() + $lifetime
            ],
            now()->addSeconds($lifetime)
        );
    }
}