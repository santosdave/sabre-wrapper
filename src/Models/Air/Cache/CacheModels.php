<?php

namespace Santosdave\SabreWrapper\Models\Air\Cache;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class InstaFlightsRequest implements SabreRequest
{
    private string $origin;
    private string $destination;
    private string $departureDate;
    private ?string $returnDate = null;
    private int $limit = 50;
    private ?string $sortBy = 'totalfare';
    private string $sortOrder = 'asc';
    private bool $onlineItinerariesOnly = false;
    private bool $eTicketsOnly = true;
    private string $pointOfSaleCountry;

    public function __construct(
        string $origin,
        string $destination,
        string $departureDate,
        string $pointOfSaleCountry
    ) {
        $this->origin = $origin;
        $this->destination = $destination;
        $this->departureDate = $departureDate;
        $this->pointOfSaleCountry = $pointOfSaleCountry;
    }

    public function setReturnDate(?string $returnDate): self
    {
        $this->returnDate = $returnDate;
        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function setSortBy(string $sortBy, string $order = 'asc'): self
    {
        $this->sortBy = $sortBy;
        $this->sortOrder = $order;
        return $this;
    }

    public function setOnlineItinerariesOnly(bool $online): self
    {
        $this->onlineItinerariesOnly = $online;
        return $this;
    }

    public function setETicketsOnly(bool $eTickets): self
    {
        $this->eTicketsOnly = $eTickets;
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

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $params = [
            'origin' => $this->origin,
            'destination' => $this->destination,
            'departuredate' => $this->departureDate,
            'pointofsalecountry' => $this->pointOfSaleCountry,
            'limit' => $this->limit,
            'sortby' => $this->sortBy,
            'order' => $this->sortOrder,
            'onlineitinerariesonly' => $this->onlineItinerariesOnly ? 'Y' : 'N',
            'eticketsonly' => $this->eTicketsOnly ? 'Y' : 'N'
        ];

        if ($this->returnDate) {
            $params['returndate'] = $this->returnDate;
        }

        return $params;
    }
}

class DestinationFinderRequest implements SabreRequest
{
    private string $origin;
    private ?string $departureDate = null;
    private ?string $returnDate = null;
    private ?int $lengthOfStay = null;
    private ?int $maxFare = null;
    private string $pointOfSaleCountry;

    public function __construct(string $origin, string $pointOfSaleCountry)
    {
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
