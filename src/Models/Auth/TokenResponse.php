<?php

namespace Santosdave\Sabre\Models\Auth;

use Santosdave\Sabre\Contracts\SabreResponse;

class TokenResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private ?string $accessToken = null;
    private ?string $tokenType = null;
    private ?int $expiresIn = null;
    private array $data;

    public function __construct(array $response)
    {
        $this->parseResponse($response);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getTokenType(): ?string
    {
        return $this->tokenType;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        if (isset($response['access_token'])) {
            $this->success = true;
            $this->accessToken = $response['access_token'];
            $this->tokenType = $response['token_type'] ?? 'bearer';
            $this->expiresIn = $response['expires_in'] ?? null;
        } else {
            $this->success = false;
            if (isset($response['error'])) {
                $this->errors[] = $response['error_description'] ?? $response['error'];
            } else {
                $this->errors[] = 'Unknown authentication error';
            }
        }
    }
}