<?php

namespace Santosdave\SabreWrapper\Models\Air\Pricing;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class ValidatePriceRequest implements SabreRequest
{

    public function validate(): bool
    {

        return true;
    }

    public function toArray(): array
    {
        $this->validate();


        return [];
    }
}
