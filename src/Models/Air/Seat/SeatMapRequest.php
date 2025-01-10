<?php

namespace Santosdave\Sabre\Models\Air\Seat;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class SeatMapRequest implements SabreRequest
{
    private string $requestType;
    private array $party;
    private array $request;

    public function __construct(string $requestType = 'payload')
    {
        $this->requestType = $requestType;
    }

    public function setTravelAgencyParty(
        string $pseudoCityId,
        string $agencyId
    ): self {
        $this->party = [
            'sender' => [
                'travelAgency' => [
                    'pseudoCityID' => $pseudoCityId,
                    'agencyID' => $agencyId
                ]
            ]
        ];
        return $this;
    }

    public function addFlightSegment(
        string $origin,
        string $destination,
        string $departureDate,
        string $carrierCode,
        string $flightNumber,
        string $bookingClass,
        string $cabinType = 'Y',
        ?string $operatingCarrier = null
    ): self {
        if (!isset($this->request['originDest'])) {
            $this->request['originDest'] = [
                'paxJourney' => [
                    'paxSegments' => []
                ]
            ];
        }

        $segment = [
            'paxSegmentId' => 'segment1',
            'departure' => [
                'locationCode' => $origin,
                'aircraftScheduledDate' => [
                    'date' => $departureDate
                ]
            ],
            'arrival' => [
                'locationCode' => $destination,
                'aircraftScheduledDate' => [
                    'date' => $departureDate
                ]
            ],
            'marketingCarrierInfo' => [
                'bookingCode' => $bookingClass,
                'carrierCode' => $carrierCode,
                'carrierFlightNumber' => $flightNumber
            ],
            'cabinType' => [
                'cabinTypeCode' => $cabinType,
                'cabinTypeName' => $this->getCabinTypeName($cabinType)
            ]
        ];

        if ($operatingCarrier) {
            $segment['operatingCarrierInfo'] = [
                'bookingCode' => $bookingClass,
                'carrierCode' => $operatingCarrier,
                'carrierFlightNumber' => $flightNumber
            ];
        } else {
            $segment['operatingCarrierInfo'] = $segment['marketingCarrierInfo'];
        }

        $this->request['originDest']['paxJourney']['paxSegments'][] = $segment;
        $this->request['paxSegmentRefIds'] = ['segment1'];

        return $this;
    }

    public function addPassenger(
        string $paxId,
        string $ptc,
        ?string $birthDate = null,
        ?string $givenName = null,
        ?string $surname = null
    ): self {
        if (!isset($this->request['paxes'])) {
            $this->request['paxes'] = [];
        }

        $passenger = [
            'paxID' => $paxId,
            'ptc' => $ptc
        ];

        if ($birthDate) {
            $passenger['birthday'] = $birthDate;
        }

        if ($givenName) {
            $passenger['givenName'] = $givenName;
        }

        if ($surname) {
            $passenger['surname'] = $surname;
        }

        $this->request['paxes'][] = $passenger;
        return $this;
    }

    private function getCabinTypeName(string $code): string
    {
        switch ($code) {
            case 'F':
                return 'First';
            case 'J':
            case 'C':
                return 'Business';
            case 'S':
                return 'Premium Economy';
            case 'Y':
                return 'Economy';
            default:
                return 'Economy';
        }
    }

    public function validate(): bool
    {
        if (empty($this->party)) {
            throw new SabreApiException('Travel agency party information is required');
        }

        if (empty($this->request['originDest']['paxJourney']['paxSegments'])) {
            throw new SabreApiException('At least one flight segment is required');
        }

        if (empty($this->request['paxes'])) {
            throw new SabreApiException('At least one passenger is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        return [
            'requestType' => $this->requestType,
            'party' => $this->party,
            'request' => $this->request
        ];
    }
}