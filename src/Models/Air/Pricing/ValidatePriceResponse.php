<?php

namespace Santosdave\Sabre\Models\Air\Pricing;

use Santosdave\Sabre\Contracts\SabreResponse;

class ValidatePriceResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
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


    private function parseResponse(array $response): void
    {
        $this->data = $response;

        $this->success = false;
        $this->errors[] = 'Invalid response format';
    }
}