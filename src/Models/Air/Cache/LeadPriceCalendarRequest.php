<?php

namespace Santosdave\SabreWrapper\Models\Air\Cache;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;


class LeadPriceCalendarRequest implements SabreRequest
{
    private string $origin;
    private string $destination;
    private ?int $lengthOfStay = null;
    private ?string $earliestDepartureDate = null;
    private ?string $latestDepartureDate = null;
    private string $pointOfSaleCountry;

    public function __construct(
        string $origin,
        string $destination,
        string $pointOfSaleCountry
    ) {
        $this->origin = $origin;
        $this->destination = $destination;
        $this->pointOfSaleCountry = $pointOfSaleCountry;
    }

    public function setLengthOfStay(int $days): self
    {
        $this->lengthOfStay = $days;
        return $this;
    }

    public function setDateRange(string $earliest, string $latest): self
    {
        $this->earliestDepartureDate = $earliest;
        $this->latestDepartureDate = $latest;
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
            'pointofsalecountry' => $this->pointOfSaleCountry
        ];

        if ($this->lengthOfStay) {
            $params['lengthofstay'] = $this->lengthOfStay;
        }

        if ($this->earliestDepartureDate) {
            $params['earliestdeparturedate'] = $this->earliestDepartureDate;
        }

        if ($this->latestDepartureDate) {
            $params['latestdeparturedate'] = $this->latestDepartureDate;
        }

        return $params;
    }
}
