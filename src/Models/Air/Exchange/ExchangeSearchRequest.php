<?php

namespace Santosdave\SabreWrapper\Models\Air\Exchange;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class ExchangeSearchRequest implements SabreRequest
{
    private string $pnr;
    private array $newSegments = [];
    private ?bool $keepOriginalItinerary = false;
    private ?array $exchangeableSegments = null;
    private ?string $currency = null;

    public function __construct(string $pnr)
    {
        $this->pnr = $pnr;
    }

    public function addNewSegment(
        string $origin,
        string $destination,
        string $departureDate,
        string $carrier,
        string $flightNumber,
        ?string $bookingClass = null
    ): self {
        $this->newSegments[] = [
            'origin' => $origin,
            'destination' => $destination,
            'departureDate' => $departureDate,
            'carrier' => $carrier,
            'flightNumber' => $flightNumber,
            'bookingClass' => $bookingClass
        ];
        return $this;
    }

    public function setKeepOriginalItinerary(bool $keep): self
    {
        $this->keepOriginalItinerary = $keep;
        return $this;
    }

    public function setExchangeableSegments(array $segments): self
    {
        $this->exchangeableSegments = $segments;
        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->pnr)) {
            throw new SabreApiException('PNR is required');
        }

        if (empty($this->newSegments)) {
            throw new SabreApiException('At least one new segment is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'OTA_AirExchangeRQ' => [
                'RetainReservation' => $this->keepOriginalItinerary,
                'ReservationLocator' => $this->pnr,
                'NewItinerary' => [
                    'OriginDestinationOptions' => [
                        'OriginDestinationOption' => array_map(function ($segment) {
                            return [
                                'FlightSegment' => [
                                    'DepartureDateTime' => $segment['departureDate'],
                                    'OriginLocation' => [
                                        'LocationCode' => $segment['origin']
                                    ],
                                    'DestinationLocation' => [
                                        'LocationCode' => $segment['destination']
                                    ],
                                    'MarketingAirline' => [
                                        'Code' => $segment['carrier'],
                                        'FlightNumber' => $segment['flightNumber']
                                    ],
                                    'ResBookDesigCode' => $segment['bookingClass']
                                ]
                            ];
                        }, $this->newSegments)
                    ]
                ]
            ]
        ];

        if ($this->exchangeableSegments) {
            $request['OTA_AirExchangeRQ']['ExchangeableSegments'] = [
                'SegmentNumber' => $this->exchangeableSegments
            ];
        }

        if ($this->currency) {
            $request['OTA_AirExchangeRQ']['PriceRequestInformation'] = [
                'CurrencyCode' => $this->currency
            ];
        }

        return $request;
    }
}
