<?php

namespace Santosdave\SabreWrapper\Models\Air\Exchange;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class ExchangeBookRequest implements SabreRequest
{
    protected $bookingDetails;

    public function __construct($bookingDetails = [])
    {
        $this->bookingDetails = $bookingDetails;
    }

    public function setBookingDetails(array $details)
    {
        $this->bookingDetails = $details;
    }

    public function getBookingDetails()
    {
        return $this->bookingDetails;
    }

    public function toArray(): array
    {
        return [];
    }

    public function validate(): bool
    {
        return true;
    }
}
