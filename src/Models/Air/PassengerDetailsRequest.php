<?php

namespace Santosdave\SabreWrapper\Models\Air;

use Santosdave\SabreWrapper\Contracts\SabreRequest;

class PassengerDetailsRequest implements SabreRequest
{
    public function validate(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return [];
    }
}
