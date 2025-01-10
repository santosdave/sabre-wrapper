<?php

namespace Santosdave\Sabre\Models\Air;

use Santosdave\Sabre\Contracts\SabreResponse;

class SearchResponse implements SabreResponse
{
    private array $data;
    private array $errors = [];
    private bool $success = false;

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
}