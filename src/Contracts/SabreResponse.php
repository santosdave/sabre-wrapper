<?php

namespace Santosdave\SabreWrapper\Contracts;

interface SabreResponse
{
    public function isSuccess(): bool;
    public function getErrors(): array;
    public function getData(): array;
}
