<?php

namespace Santosdave\Sabre\Models\Air;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

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