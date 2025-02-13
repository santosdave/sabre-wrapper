<?php

namespace Santosdave\SabreWrapper\Models\Air\Cache;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;


class DestinationFinderRequest implements SabreRequest
{
    private string $origin;
    private ?string $departureDate = null;
    private ?string $returnDate = null;
    private ?int $lengthOfStay = null;
    private ?int $maxFare = null;
    private string $pointOfSaleCountry;

    public function __construct(
        string $origin,
        string $pointOfSaleCountry
    ) {
        $this->origin = $origin;
        $this->pointOfSaleCountry = $pointOfSaleCountry;
    }

    public function setDates(?string $departureDate, ?string $returnDate = null): self
    {
        $this->departureDate = $departureDate;
        $this->returnDate = $returnDate;
        return $this;
    }

    public function setLengthOfStay(int $days): self
    {
        $this->lengthOfStay = $days;
        return $this;
    }

    public function setMaxFare(int $fare): self
    {
        $this->maxFare = $fare;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->origin)) {
            throw new SabreApiException('Origin is required');
        }

        if ($this->lengthOfStay !== null && $this->lengthOfStay < 1) {
            throw new SabreApiException('Length of stay must be greater than 0');
        }

        if ($this->maxFare !== null && $this->maxFare < 1) {
            throw new SabreApiException('Max fare must be greater than 0');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $params = [
            'origin' => $this->origin,
            'pointofsalecountry' => $this->pointOfSaleCountry
        ];

        if ($this->departureDate) {
            $params['departuredate'] = $this->departureDate;
        }

        if ($this->returnDate) {
            $params['returndate'] = $this->returnDate;
        }

        if ($this->lengthOfStay) {
            $params['lengthofstay'] = $this->lengthOfStay;
        }

        if ($this->maxFare) {
            $params['maxfare'] = $this->maxFare;
        }

        return $params;
    }
}
