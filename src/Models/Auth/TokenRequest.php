<?php

namespace Santosdave\SabreWrapper\Models\Auth;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class TokenRequest implements SabreRequest
{
    private string $grantType = 'client_credentials';
    private string $clientId;
    private string $clientSecret;
    private ?string $username = null;
    private ?string $password = null;
    private ?string $domain = null;

    private string $type = 'rest';

    public function __construct(string $clientId, string $clientSecret, string $type = 'rest')
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->type = $type;
    }

    public function setCredentials(?string $username, ?string $password): self
    {
        $this->username = $username;
        $this->password = $password;
        return $this;
    }

    public function setDomain(?string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new SabreApiException('Client ID and Client Secret are required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        switch ($this->type) {
            case 'rest':
                return $this->toRestArray();
            case 'soap_session':
                return $this->toSoapSessionArray();
            case 'soap_stateless':
                return $this->toSoapStatelessArray();
            default:
                throw new \InvalidArgumentException("Invalid token type: {$this->type}");
        }
    }

    private function toRestArray(): array
    {
        return [
            'grant_type' => $this->grantType,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => $this->username,
            'password' => $this->password,
            'domain' => $this->domain ?? 'AA'
        ];
    }

    private function toSoapSessionArray(): array
    {
        // Return SOAP session format
        return [];
    }

    private function toSoapStatelessArray(): array
    {
        // Return SOAP stateless format
        return [];
    }
}
