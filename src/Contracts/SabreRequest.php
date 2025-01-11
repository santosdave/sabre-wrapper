<?php

namespace Santosdave\SabreWrapper\Contracts;

interface SabreRequest
{
    public function toArray(): array;
    public function validate(): bool;
}
