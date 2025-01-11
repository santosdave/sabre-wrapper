<?php

namespace Santosdave\SabreWrapper\Exceptions\Auth;

use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class SabreAuthenticationException extends SabreApiException
{
    protected ?string $tokenType = null;
    protected ?int $expiresIn = null;

    public function __construct(
        string $message = "",
        int $code = 401,
        ?string $tokenType = null,
        ?int $expiresIn = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, null, 'AUTH_ERROR', null, $previous);
        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
    }

    public function getTokenType(): ?string
    {
        return $this->tokenType;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }
}
