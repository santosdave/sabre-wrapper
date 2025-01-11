<?php

namespace Santosdave\SabreWrapper\Exceptions;

use Exception;

class SabreApiException extends Exception
{
    protected ?array $errorDetails = null;
    protected ?string $errorCode = null;
    protected ?string $requestId = null;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?array $errorDetails = null,
        ?string $errorCode = null,
        ?string $requestId = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorDetails = $errorDetails;
        $this->errorCode = $errorCode;
        $this->requestId = $requestId;
    }

    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'error_code' => $this->getErrorCode(),
            'error_details' => $this->getErrorDetails(),
            'request_id' => $this->getRequestId()
        ];
    }
}
