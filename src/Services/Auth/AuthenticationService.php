<?php

namespace Santosdave\SabreWrapper\Services\Auth;

use GuzzleHttp\Client;
use InvalidArgumentException;
use Santosdave\SabreWrapper\Exceptions\Auth\SabreAuthenticationException;

class AuthenticationService
{
    private SessionManager $sessionManager;

    public function __construct(
        SessionManager $sessionManager,
        private string $username,
        private string $password,
        private string $pcc,
        private string $environment,
        private string $clientId,
        private string $clientSecret
    ) {
        $this->sessionManager = $sessionManager;
    }



    public function getToken(string $type = 'rest'): string
    {
        switch ($type) {
            case 'rest':
                return $this->getRestToken();
            case 'soap_session':
                return $this->sessionManager->acquireSession();
            case 'soap_stateless':
                return $this->getSoapToken();
            default:
                throw new InvalidArgumentException("Invalid token type: {$type}");
        }
    }

    private function getSoapToken(): string
    {
        try {
            if ($token = $this->getCachedToken('soap_stateless')) {
                return $token;
            }

            throw new \RuntimeException('SOAP token generation not implemented');
        } catch (\Exception $e) {
            throw new SabreAuthenticationException(
                "SOAP authentication failed: {$e->getMessage()}",
                401
            );
        }
    }

    private function getRestToken(): string
    {
        try {
            if ($token = $this->getCachedToken('rest')) {
                return $token;
            }

            $token = $this->requestNewRestToken();
            $this->cacheToken('rest', $token);
            return $token;
        } catch (\Exception $e) {
            throw new SabreAuthenticationException(
                "REST authentication failed: {$e->getMessage()}",
                401
            );
        }
    }

    private function requestNewRestToken(): string
    {
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
        return $data['access_token'];
    }
}