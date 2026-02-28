<?php

namespace Santosdave\SabreWrapper\Models\Air;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class CreatePnrRequest implements SabreRequest
{
    private array $segments = [];
    private array $passengers = [];
    private array $contacts = [];
    private ?string $ticketType = '7TAW';
    private ?string $receivedFrom = 'API';

    public function addSegment(
        string $origin,
        string $destination,
        string $departureDate,
        string $flightNumber,
        string $carrier,
        string $bookingClass,
        string $departureTime,
        ?string $arrivalTime = null
    ): self {
        $this->segments[] = [
            'origin' => $origin,
            'destination' => $destination,
            'departureDate' => $departureDate,
            'departureTime' => $departureTime,
            'arrivalTime' => $arrivalTime,
            'flightNumber' => $flightNumber,
            'carrier' => $carrier,
            'bookingClass' => $bookingClass,
            'status' => 'NN',
            'numberInParty' => count($this->passengers) ?: 1
        ];
        return $this;
    }

    public function addPassenger(
        string $firstName,
        string $lastName,
        string $type = 'ADT',
        ?string $dateOfBirth = null,
        ?string $gender = null
    ): self {
        $this->passengers[] = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'type' => $type,
            'dateOfBirth' => $dateOfBirth,
            'gender' => $gender,
            'nameNumber' => (count($this->passengers) + 1) . '.1'
        ];
        return $this;
    }

    public function addContact(
        string $type,
        string $value,
        ?string $nameNumber = null
    ): self {
        $this->contacts[] = [
            'type' => $type,
            'value' => $value,
            'nameNumber' => $nameNumber
        ];
        return $this;
    }

    public function setTicketType(string $type): self
    {
        $this->ticketType = $type;
        return $this;
    }

    public function setReceivedFrom(string $receivedFrom): self
    {
        $this->receivedFrom = $receivedFrom;
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
            'segments' => $this->segments,
            'passengers' => $this->passengers,
            'contacts' => $this->contacts,
            'ticketType' => $this->ticketType,
            'receivedFrom' => $this->receivedFrom
        ];
    }

    public function toSoapArray(): array
    {
        $data = $this->toArray();
        return [
            'CreatePassengerNameRecordRQ' => [
                'version' => '2.4.0',
                'TravelItineraryAddInfo' => [
                    'AgencyInfo' => [
                        'Ticketing' => ['TicketType' => $data['ticketType']]
                    ],
                    'CustomerInfo' => $this->formatCustomerInfo($data)
                ],
                'AirBook' => [
                    'HaltOnStatus' => [
                        ['Code' => 'NN'],
                        ['Code' => 'UC'],
                        ['Code' => 'UN']
                    ],
                    'OriginDestinationInformation' => $this->formatSegments($data['segments']),
                    'RedisplayReservation' => [
                        'NumAttempts' => 3,
                        'WaitInterval' => 2000
                    ]
                ],
                'PostProcessing' => [
                    'EndTransaction' => [
                        'Source' => ['ReceivedFrom' => $data['receivedFrom']]
                    ],
                    'RedisplayReservation' => ['waitInterval' => 100]
                ]
            ]
        ];
    }

    private function formatCustomerInfo(array $data): array
    {
        $customerInfo = [
            'ContactNumbers' => [],
            'PersonName' => []
        ];

        foreach ($data['contacts'] as $contact) {
            if (str_starts_with($contact['type'], 'PHONE')) {
                $customerInfo['ContactNumbers'][] = [
                    'Phone' => $contact['value'],
                    'PhoneUseType' => substr($contact['type'], 6),
                    'NameNumber' => $contact['nameNumber']
                ];
            } elseif ($contact['type'] === 'EMAIL') {
                $customerInfo['Email'][] = [
                    'Address' => $contact['value'],
                    'NameNumber' => $contact['nameNumber'],
                    'Type' => 'TO'
                ];
            }
        }

        foreach ($data['passengers'] as $passenger) {
            $customerInfo['PersonName'][] = [
                'NameNumber' => $passenger['nameNumber'],
                'GivenName' => $passenger['firstName'],
                'Surname' => $passenger['lastName'],
                'PassengerType' => $passenger['type']
            ];
        }

        return $customerInfo;
    }

    private function formatSegments(array $segments): array
    {
        return array_map(function ($segment) {
            return [
                'FlightSegment' => [
                    'DepartureDateTime' => $segment['departureDate'] . 'T' . $segment['departureTime'],
                    'FlightNumber' => $segment['flightNumber'],
                    'NumberInParty' => $segment['numberInParty'],
                    'ResBookDesigCode' => $segment['bookingClass'],
                    'Status' => $segment['status'],
                    'DestinationLocation' => ['LocationCode' => $segment['destination']],
                    'MarketingAirline' => [
                        'Code' => $segment['carrier'],
                        'FlightNumber' => $segment['flightNumber']
                    ],
                    'OriginLocation' => ['LocationCode' => $segment['origin']]
                ]
            ];
        }, $segments);
    }
}
