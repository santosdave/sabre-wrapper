<?php

namespace Santosdave\SabreWrapper\Models\Air;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class SearchRequest implements SabreRequest
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
