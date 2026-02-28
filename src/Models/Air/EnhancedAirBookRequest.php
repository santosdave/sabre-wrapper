<?php

namespace Santosdave\SabreWrapper\Models\Air;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class EnhancedAirBookRequest implements SabreRequest
{
    private array $segments = [];
    private array $passengers = [];
    private ?string $currency = null;
    public bool $priceItinerary = true;

    public function addSegment(
        string $origin,
        string $destination,
        string $departureDateTime,
        string $flightNumber,
        string $carrier,
        string $bookingClass
    ): self {
        $this->segments[] = [
            'origin' => $origin,
            'destination' => $destination,
            'departureDateTime' => $departureDateTime,
            'flightNumber' => $flightNumber,
            'carrier' => $carrier,
            'bookingClass' => $bookingClass
        ];
        return $this;
    }

    public function addPassenger(string $type, int $quantity): self
    {
        $this->passengers[] = [
            'type' => $type,
            'quantity' => $quantity
        ];
        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function setPriceItinerary(bool $price): self
    {
        $this->priceItinerary = $price;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->segments)) {
            throw new SabreApiException('At least one segment is required');
        }

        if (empty($this->passengers)) {
            throw new SabreApiException('At least one passenger type is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();
        return [
            'segments' => $this->segments,
            'passengers' => $this->passengers,
            'currency' => $this->currency,
            'priceItinerary' => $this->priceItinerary
        ];
    }
}
