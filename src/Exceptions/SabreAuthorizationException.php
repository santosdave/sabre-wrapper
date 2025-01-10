<?php

namespace Santosdave\Sabre\Exceptions;

class SabreAuthorizationException extends SabreApiException
{
    protected ?string $requiredScope = null;
    protected ?array $availableScopes = null;
    protected ?string $pcc = null;

    public function __construct(
        string $message = "Authorization failed",
        int $code = 403,
        ?string $errorCode = null,
        ?string $requiredScope = null,
        ?array $availableScopes = null,
        ?string $pcc = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, null, $errorCode, null, $previous);
        $this->requiredScope = $requiredScope;
        $this->availableScopes = $availableScopes;
        $this->pcc = $pcc;
    }

    public function getRequiredScope(): ?string
    {
        return $this->requiredScope;
    }

    public function getAvailableScopes(): ?array
    {
        return $this->availableScopes;
    }

    public function getPcc(): ?string
    {
        return $this->pcc;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'required_scope' => $this->requiredScope,
            'available_scopes' => $this->availableScopes,
            'pcc' => $this->pcc
        ]);
    }
}