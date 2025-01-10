<?php

namespace Santosdave\Sabre\Models\Air\Pricing;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

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