<?php

namespace Santosdave\SabreWrapper\Exceptions;

class SabreRateLimitException extends SabreApiException
{
    protected ?int $limit = null;
    protected ?int $remaining = null;
    protected ?int $reset = null;
    protected ?string $retryAfter = null;

    public function __construct(
        string $message = "Rate limit exceeded",
        int $code = 429,
        ?string $errorCode = null,
        ?int $limit = null,
        ?int $remaining = null,
        ?int $reset = null,
        ?string $retryAfter = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, null, $errorCode, null, $previous);
        $this->limit = $limit;
        $this->remaining = $remaining;
        $this->reset = $reset;
        $this->retryAfter = $retryAfter;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getRemaining(): ?int
    {
        return $this->remaining;
    }

    public function getReset(): ?int
    {
        return $this->reset;
    }

    public function getRetryAfter(): ?string
    {
        return $this->retryAfter;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'limit' => $this->limit,
            'remaining' => $this->remaining,
            'reset' => $this->reset,
            'retry_after' => $this->retryAfter
        ]);
    }

    public function shouldRetry(): bool
    {
        return !is_null($this->retryAfter);
    }

    public function getRetryDelayInSeconds(): int
    {
        if (!$this->retryAfter) {
            return 0;
        }

        // Handle both Unix timestamp and delay-seconds formats
        if (is_numeric($this->retryAfter)) {
            // Unix timestamp
            $retryTime = (int) $this->retryAfter;
            return max(0, $retryTime - time());
        } else {
            // HTTP-date format
            $retryTime = strtotime($this->retryAfter);
            return max(0, $retryTime - time());
        }
    }
}
