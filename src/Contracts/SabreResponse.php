<?php

namespace Santosdave\Sabre\Contracts;

interface SabreResponse
{
    public function isSuccess(): bool;
    public function getErrors(): array;
    public function getData(): array;
}