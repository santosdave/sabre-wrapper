<?php

namespace Santosdave\SabreWrapper\Models\Air\Pricing;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class PriceItineraryRequest implements SabreRequest
{
    private array $segments = [];
    private array $passengers = [];
    private ?string $currency = null;
    private array $pricingQualifiers = [];
    private bool $returnBrandedFares = false;
    private bool $returnMileage = false;

    public function addSegment(
        string $origin,
        string $destination,
        string $carrier,
        string $flightNumber,
        string $departureDateTime,
        string $bookingClass
    ): self {
        $this->segments[] = [
            'origin' => $origin,
            'destination' => $destination,
            'carrier' => $carrier,
            'flightNumber' => $flightNumber,
            'departureDateTime' => $departureDateTime,
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

    public function addPricingQualifier(string $qualifier, $value): self
    {
        $this->pricingQualifiers[$qualifier] = $value;
        return $this;
    }

    public function setReturnBrandedFares(bool $return): self
    {
        $this->returnBrandedFares = $return;
        return $this;
    }

    public function setReturnMileage(bool $return): self
    {
        $this->returnMileage = $return;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->segments)) {
            throw new SabreApiException('At least one segment is required');
        }

        if (empty($this->passengers)) {
            throw new SabreApiException('At least one passenger is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        return [
            'OTA_AirPriceRQ' => [
                'PriceRequestInformation' => [
                    'OptionalQualifiers' => [
                        'PricingQualifiers' => array_merge(
                            $this->formatPassengerTypes(),
                            $this->pricingQualifiers
                        ),
                        'BrandedFareIndicators' => [
                            'ReturnBrandedFares' => $this->returnBrandedFares
                        ]
                    ]
                ],
                'AirItinerary' => [
                    'OriginDestinationOptions' => [
                        'OriginDestinationOption' => $this->formatSegments()
                    ]
                ]
            ]
        ];
    }

    private function formatPassengerTypes(): array
    {
        return [
            'PassengerType' => array_map(function ($passenger) {
                return [
                    'Code' => $passenger['type'],
                    'Quantity' => $passenger['quantity']
                ];
            }, $this->passengers)
        ];
    }

    private function formatSegments(): array
    {
        return array_map(function ($segment) {
            return [
                'FlightSegment' => [
                    'DepartureDateTime' => $segment['departureDateTime'],
                    'FlightNumber' => $segment['flightNumber'],
                    'ResBookDesigCode' => $segment['bookingClass'],
                    'DestinationLocation' => [
                        'LocationCode' => $segment['destination']
                    ],
                    'MarketingAirline' => [
                        'Code' => $segment['carrier']
                    ],
                    'OriginLocation' => [
                        'LocationCode' => $segment['origin']
                    ]
                ]
            ];
        }, $this->segments);
    }
}
