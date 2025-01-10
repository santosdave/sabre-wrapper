<?php

namespace Santosdave\Sabre\Models\Air;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

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

class CreatePnrResponse implements \Santosdave\Sabre\Contracts\SabreResponse
{
    private bool $success;
    private array $errors = [];
    private ?string $pnr = null;
    private array $data;

    public function __construct(array $response, string $type = 'rest')
    {
        $this->parseResponse($response, $type);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getPnr(): ?string
    {
        return $this->pnr;
    }

    private function parseResponse(array $response, string $type): void
    {
        $this->data = $response;

        if ($type === 'soap') {
            $this->parseSoapResponse($response);
        } else {
            $this->parseRestResponse($response);
        }
    }

    private function parseSoapResponse(array $response): void
    {
        if (isset($response['CreatePassengerNameRecordRS'])) {
            $rs = $response['CreatePassengerNameRecordRS'];
            $this->success = true;

            if (isset($rs['ItineraryRef']['ID'])) {
                $this->pnr = $rs['ItineraryRef']['ID'];
            }

            if (isset($rs['ApplicationResults']['Error'])) {
                $this->success = false;
                $this->errors = array_map(function ($error) {
                    return $error['SystemSpecificResults']['Message'];
                }, (array) $rs['ApplicationResults']['Error']);
            }
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid SOAP response format';
        }
    }

    private function parseRestResponse(array $response): void
    {
        if (isset($response['CreatePassengerNameRecordResponse'])) {
            $rs = $response['CreatePassengerNameRecordResponse'];
            $this->success = true;

            if (isset($rs['pnr'])) {
                $this->pnr = $rs['pnr'];
            }

            if (isset($rs['errors'])) {
                $this->success = false;
                $this->errors = $rs['errors'];
            }
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid REST response format';
        }
    }
}

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

    // Additional methods as needed...
}


class PassengerDetailsRequest implements SabreRequest {
    public function validate(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return [];
    }
}