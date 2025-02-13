<?php

namespace Santosdave\SabreWrapper\Models\Intelligence;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class SeasonalityRequest implements SabreRequest
{
    private string $destination;
    private ?string $origin = null;
    private ?string $pointOfSaleCountry = null;
    private ?int $lengthOfStay = null;

    public function __construct(string $destination)
    {
        $this->destination = $destination;
    }

    public function setOrigin(?string $origin): self
    {
        $this->origin = $origin;
        return $this;
    }

    public function setPointOfSaleCountry(?string $country): self
    {
        $this->pointOfSaleCountry = $country;
        return $this;
    }

    public function setLengthOfStay(?int $days): self
    {
        $this->lengthOfStay = $days;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->destination)) {
            throw new SabreApiException('Destination is required');
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
            'destination' => $this->destination
        ];

        if ($this->origin) {
            $params['origin'] = $this->origin;
        }

        if ($this->pointOfSaleCountry) {
            $params['pointofsalecountry'] = $this->pointOfSaleCountry;
        }

        if ($this->lengthOfStay) {
            $params['lengthofstay'] = $this->lengthOfStay;
        }

        return $params;
    }
}