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
        string $returnDate,
        string $pointOfSaleCountry
    ) {
        $this->origin = $origin;
        $this->destination = $destination;
        $this->departureDate = $departureDate;
        $this->returnDate = $returnDate;
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
