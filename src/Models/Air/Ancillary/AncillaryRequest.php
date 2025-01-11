<?php

namespace Santosdave\SabreWrapper\Models\Air\Ancillary;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class AncillaryRequest implements SabreRequest
{
    private string $requestType;
    private array $party;
    private array $request;
    private ?array $paxSegmentRefIds = [];
    private array $paxes = [];

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
        string $segmentId,
        string $origin,
        string $destination,
        string $departureDate,
        string $carrierCode,
        string $flightNumber,
        string $bookingClass,
        ?string $operatingCarrierCode = null
    ): self {
        $this->paxSegmentRefIds[] = $segmentId;

        if (!isset($this->request['originDest'])) {
            $this->request['originDest'] = [
                'paxJourney' => [
                    'paxSegments' => []
                ]
            ];
        }

        $segment = [
            'paxSegmentId' => $segmentId,
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
                'cabinTypeCode' => 'Y',
                'cabinTypeName' => 'Economy'
            ]
        ];

        if ($operatingCarrierCode) {
            $segment['operatingCarrierInfo'] = [
                'bookingCode' => $bookingClass,
                'carrierCode' => $operatingCarrierCode,
                'carrierFlightNumber' => $flightNumber
            ];
        } else {
            $segment['operatingCarrierInfo'] = $segment['marketingCarrierInfo'];
        }

        $this->request['originDest']['paxJourney']['paxSegments'][] = $segment;
        return $this;
    }

    public function addPassenger(
        string $paxId,
        string $ptc,
        ?string $birthDate = null,
        ?string $givenName = null,
        ?string $surname = null
    ): self {
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

        $this->paxes[] = $passenger;
        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->request['currency'] = $currency;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->party)) {
            throw new SabreApiException('Travel agency party information is required');
        }

        if (empty($this->request['originDest']['paxJourney']['paxSegments'])) {
            throw new SabreApiException('At least one flight segment is required');
        }

        if (empty($this->paxes)) {
            throw new SabreApiException('At least one passenger is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'requestType' => $this->requestType,
            'party' => $this->party,
            'request' => array_merge(
                [
                    'paxSegmentRefIds' => $this->paxSegmentRefIds,
                    'paxes' => $this->paxes
                ],
                $this->request
            )
        ];

        return $request;
    }
}
