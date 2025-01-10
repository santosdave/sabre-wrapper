<?php

namespace Santosdave\Sabre\Contracts;

interface SabreRequest
{
    public function toArray(): array;
    public function validate(): bool;
}