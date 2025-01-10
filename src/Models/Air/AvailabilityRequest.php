<?php

namespace Santosdave\Sabre\Models\Air;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class AvailabilityRequest implements SabreRequest
{
    private string $origin;
    private string $destination;
    private string $departureDate;
    private ?string $returnDate = null;
    private int $passengerCount = 1;
    private ?string $preferredCarrier = null;
    private bool $directFlights = false;

    public function setOrigin(string $origin): self
    {
        $this->origin = $origin;
        return $this;
    }

    public function setDestination(string $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    public function setDepartureDate(string $date): self
    {
        $this->departureDate = $date;
        return $this;
    }

    public function setReturnDate(?string $date): self
    {
        $this->returnDate = $date;
        return $this;
    }

    public function setPassengerCount(int $count): self
    {
        $this->passengerCount = $count;
        return $this;
    }

    public function setPreferredCarrier(?string $carrier): self
    {
        $this->preferredCarrier = $carrier;
        return $this;
    }

    public function setDirectFlights(bool $direct): self
    {
        $this->directFlights = $direct;
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

        if ($this->passengerCount < 1) {
            throw new SabreApiException('Passenger count must be at least 1');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        // Base structure that works for both REST and SOAP
        $data = [
            'OriginDestinationInformation' => [
                'FlightSegment' => [
                    'DepartureDateTime' => $this->departureDate,
                    'OriginLocation' => [
                        'LocationCode' => $this->origin
                    ],
                    'DestinationLocation' => [
                        'LocationCode' => $this->destination
                    ]
                ]
            ]
        ];

        if ($this->preferredCarrier) {
            $data['OptionalQualifiers']['FlightQualifiers']['VendorPrefs'][] = [
                'Airline' => ['Code' => $this->preferredCarrier]
            ];
        }

        if ($this->directFlights) {
            $data['OptionalQualifiers']['FlightQualifiers']['DirectFlightsOnly'] = true;
        }

        return $data;
    }

    public function toSoapArray(): array
    {
        // SOAP-specific structure for OTA_AirAvailRQ
        return [
            'OTA_AirAvailRQ' => array_merge(
                [
                    'Version' => '2.4.0',
                    'ReturnHostCommand' => true
                ],
                $this->toArray()
            )
        ];
    }

    public function toRestArray(): array
    {
        // REST-specific structure
        return [
            'AirSchedulesAndAvailabilityRQ' => array_merge(
                [
                    'version' => '5.3.1',
                    'OriginDestination' => [
                        'date' => $this->departureDate,
                        'origin' => $this->origin,
                        'destination' => $this->destination,
                        'numOfPassengers' => $this->passengerCount
                    ]
                ],
                $this->directFlights ? ['Options' => ['Routing' => ['directFlightsOnly' => true]]] : []
            )
        ];
    }
}