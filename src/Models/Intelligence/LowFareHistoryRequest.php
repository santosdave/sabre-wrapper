<?php

namespace Santosdave\SabreWrapper\Models\Intelligence;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;


class LowFareHistoryRequest implements SabreRequest
{
    private string $origin;
    private string $destination;
    private string $departureDate;
    private ?string $returnDate = null;
    private string $pointOfSaleCountry;
    private ?int $lengthOfStay = null;

    public function __construct(
        string $origin,
        string $destination,
        string $departureDate,
        string $returnDate,
        string $pointOfSaleCountry
    ) {
        $this->origin = $origin;
        $this->destination = $destination;
        $this->departureDate = $departureDate;
        $this->returnDate = $returnDate ?? $departureDate;
        $this->pointOfSaleCountry = $pointOfSaleCountry;
    }

    public function setReturnDate(?string $returnDate): self
    {
        $this->returnDate = $returnDate;
        return $this;
    }

    public function setLengthOfStay(?int $days): self
    {
        $this->lengthOfStay = $days;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->origin)) {
            throw new SabreApiException('Origin is required');
        }

        if (empty($this->destination)) {
            throw new SabreApiException('Destination is required');
        }

        if (empty($this->departureDate)) {
            throw new SabreApiException('Departure date is required');
        }

        if (empty($this->pointOfSaleCountry)) {
            throw new SabreApiException('Point of sale country is required');
        }

        if ($this->lengthOfStay !== null && $this->lengthOfStay < 1) {
            throw new SabreApiException('Length of stay must be greater than 0');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $params = [
            'origin' => $this->origin,
            'destination' => $this->destination,
            'departuredate' => $this->departureDate,
            'pointofsalecountry' => $this->pointOfSaleCountry
        ];

        if ($this->returnDate) {
            $params['returndate'] = $this->returnDate;
        }

        if ($this->lengthOfStay) {
            $params['lengthofstay'] = $this->lengthOfStay;
        }

        return $params;
    }
}
