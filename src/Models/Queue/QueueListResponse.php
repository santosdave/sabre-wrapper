<?php

namespace Santosdave\Sabre\Models\Queue;

use Santosdave\Sabre\Contracts\SabreResponse;

class QueueListResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private array $entries = [];
    private ?array $summary = null;

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

    public function getEntries(): array
    {
        return $this->entries;
    }

    public function getSummary(): ?array
    {
        return $this->summary;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        if (isset($response['QueueAccessRS'])) {
            $this->parseQueueResponse($response['QueueAccessRS']);
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid response format';
        }
    }

    private function parseQueueResponse(array $response): void
    {
        if (isset($response['Errors'])) {
            $this->success = false;
            $this->errors = $this->parseErrors($response['Errors']);
            return;
        }

        $this->success = true;

        if (isset($response['QueueList'])) {
            $this->parseQueueEntries($response['QueueList']);
        }

        if (isset($response['QueueSummary'])) {
            $this->parseSummary($response['QueueSummary']);
        }
    }

    private function parseErrors(array $errors): array
    {
        return array_map(function ($error) {
            return [
                'code' => $error['Code'] ?? null,
                'message' => $error['Message'] ?? 'Unknown error',
                'type' => $error['Type'] ?? null
            ];
        }, (array) $errors['Error']);
    }

    private function parseQueueEntries(array $queueList): void
    {
        foreach ((array) $queueList['QueueEntry'] as $entry) {
            $this->entries[] = [
                'pnr' => $entry['RecordLocator'],
                'dateTime' => $entry['DateTime'],
                'agent' => $entry['Agent'] ?? null,
                'category' => $entry['Category'] ?? null,
                'carrier' => $entry['Carrier'] ?? null,
                'passengers' => $this->parsePassengers($entry['Passengers'] ?? []),
                'itinerary' => $this->parseItinerary($entry['Itinerary'] ?? []),
                'remarks' => $entry['Remarks'] ?? []
            ];
        }
    }

    private function parsePassengers(array $passengers): array
    {
        return array_map(function ($passenger) {
            return [
                'nameNumber' => $passenger['NameNumber'],
                'firstName' => $passenger['FirstName'],
                'lastName' => $passenger['LastName'],
                'type' => $passenger['Type'] ?? null
            ];
        }, (array) ($passengers['Passenger'] ?? []));
    }

    private function parseItinerary(array $itinerary): array
    {
        return array_map(function ($segment) {
            return [
                'carrier' => $segment['Carrier'],
                'flightNumber' => $segment['FlightNumber'],
                'departure' => [
                    'airport' => $segment['DepartureAirport'],
                    'date' => $segment['DepartureDate'],
                    'time' => $segment['DepartureTime']
                ],
                'arrival' => [
                    'airport' => $segment['ArrivalAirport']
                ],
                'status' => $segment['Status'] ?? null
            ];
        }, (array) ($itinerary['Segment'] ?? []));
    }

    private function parseSummary(array $summary): void
    {
        $this->summary = [
            'totalCount' => $summary['TotalCount'] ?? 0,
            'categories' => array_map(function ($category) {
                return [
                    'number' => $category['Number'],
                    'count' => $category['Count']
                ];
            }, (array) ($summary['CategorySummary'] ?? [])),
            'carriers' => array_map(function ($carrier) {
                return [
                    'code' => $carrier['Code'],
                    'count' => $carrier['Count']
                ];
            }, (array) ($summary['CarrierSummary'] ?? []))
        ];
    }
}
