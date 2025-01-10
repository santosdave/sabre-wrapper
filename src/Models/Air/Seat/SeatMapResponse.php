<?php

namespace Santosdave\Sabre\Models\Air\Seat;

use Santosdave\Sabre\Contracts\SabreResponse;

class SeatMapResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private array $cabins = [];
    private ?array $aircraft = null;
    private ?array $seatAvailability = null;
    private ?array $pricing = null;

    public function __construct(array $response)
    {
        $this->parseResponse($response);
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

    public function getCabins(): array
    {
        return $this->cabins;
    }

    public function getAircraft(): ?array
    {
        return $this->aircraft;
    }

    public function getSeatAvailability(): ?array
    {
        return $this->seatAvailability;
    }

    public function getPricing(): ?array
    {
        return $this->pricing;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        if (isset($response['Errors'])) {
            $this->success = false;
            $this->errors = $this->parseErrors($response['Errors']);
            return;
        }

        $this->success = true;

        if (isset($response['SeatMap'])) {
            $this->parseSeatMap($response['SeatMap']);
        }

        if (isset($response['Aircraft'])) {
            $this->parseAircraft($response['Aircraft']);
        }

        if (isset($response['SeatAvailability'])) {
            $this->parseSeatAvailability($response['SeatAvailability']);
        }

        if (isset($response['Pricing'])) {
            $this->parsePricing($response['Pricing']);
        }
    }

    private function parseErrors(array $errors): array
    {
        return array_map(function ($error) {
            return [
                'code' => $error['Code'] ?? null,
                'message' => $error['Message'] ?? 'Unknown error',
                'type' => $error['Type'] ?? null,
                'details' => $error['Details'] ?? null
            ];
        }, (array) $errors['Error']);
    }

    private function parseSeatMap(array $seatMap): void
    {
        foreach ($seatMap as $cabin) {
            if (isset($cabin['Cabin'])) {
                $this->cabins[] = [
                    'type' => $cabin['CabinType'] ?? null,
                    'layout' => $this->parseCabinLayout($cabin['Layout'] ?? []),
                    'rows' => $this->parseRows($cabin['Rows'] ?? []),
                    'facilities' => $this->parseFacilities($cabin['Facilities'] ?? [])
                ];
            }
        }
    }

    private function parseCabinLayout(array $layout): array
    {
        return [
            'firstSeatLetter' => $layout['FirstSeatLetter'] ?? null,
            'lastSeatLetter' => $layout['LastSeatLetter'] ?? null,
            'startingRow' => $layout['StartingRow'] ?? null,
            'endingRow' => $layout['EndingRow'] ?? null,
            'seatsAbreast' => $layout['SeatsAbreast'] ?? null
        ];
    }

    private function parseRows(array $rows): array
    {
        return array_map(function ($row) {
            return [
                'number' => $row['RowNumber'],
                'seats' => array_map(function ($seat) {
                    return [
                        'number' => $seat['Number'],
                        'letter' => $seat['Letter'],
                        'availability' => $seat['Availability'],
                        'characteristics' => $seat['Characteristics'] ?? [],
                        'price' => $seat['Price'] ?? null,
                        'restrictions' => $seat['Restrictions'] ?? []
                    ];
                }, $row['Seats'] ?? [])
            ];
        }, $rows);
    }

    private function parseFacilities(array $facilities): array
    {
        return array_map(function ($facility) {
            return [
                'type' => $facility['Type'],
                'location' => $facility['Location'],
                'rowNumber' => $facility['RowNumber'] ?? null,
                'description' => $facility['Description'] ?? null
            ];
        }, $facilities);
    }

    private function parseAircraft(array $aircraft): void
    {
        $this->aircraft = [
            'type' => $aircraft['Type'],
            'configuration' => $aircraft['Configuration'] ?? null,
            'details' => [
                'manufacturer' => $aircraft['Details']['Manufacturer'] ?? null,
                'model' => $aircraft['Details']['Model'] ?? null,
                'seatsTotal' => $aircraft['Details']['SeatsTotal'] ?? null
            ]
        ];
    }

    private function parseSeatAvailability(array $availability): void
    {
        $this->seatAvailability = array_map(function ($entry) {
            return [
                'cabin' => $entry['Cabin'],
                'availableSeats' => $entry['AvailableSeats'],
                'restrictions' => $entry['Restrictions'] ?? [],
                'seatFeatures' => $entry['SeatFeatures'] ?? []
            ];
        }, $availability);
    }

    private function parsePricing(array $pricing): void
    {
        $this->pricing = [
            'currency' => $pricing['Currency'],
            'categories' => array_map(function ($category) {
                return [
                    'code' => $category['Code'],
                    'name' => $category['Name'],
                    'amount' => $category['Amount'],
                    'features' => $category['Features'] ?? [],
                    'restrictions' => $category['Restrictions'] ?? []
                ];
            }, $pricing['Categories'] ?? [])
        ];
    }
}