<?php

namespace Santosdave\SabreWrapper\Models\Air\Booking;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class CreateBookingResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private ?string $confirmationId = null;
    private ?array $itinerary = null;
    private ?array $travelers = null;
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

    public function getConfirmationId(): ?string
    {
        return $this->confirmationId;
    }

    public function getItinerary(): ?array
    {
        return $this->itinerary;
    }

    public function getTravelers(): ?array
    {
        return $this->travelers;
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
        $this->confirmationId = $response['confirmationId'] ?? null;

        if (isset($response['Itinerary'])) {
            $this->parseItinerary($response['Itinerary']);
        }

        if (isset($response['Travelers'])) {
            $this->parseTravelers($response['Travelers']);
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
                'type' => $error['Type'] ?? null
            ];
        }, (array) $errors['Error']);
    }

    private function parseItinerary(array $itinerary): void
    {
        $this->itinerary = [
            'segments' => array_map(function ($segment) {
                return [
                    'departureAirport' => $segment['DepartureAirport'],
                    'arrivalAirport' => $segment['ArrivalAirport'],
                    'departureTime' => $segment['DepartureTime'],
                    'arrivalTime' => $segment['ArrivalTime'],
                    'carrier' => $segment['MarketingCarrier'],
                    'flightNumber' => $segment['FlightNumber'],
                    'bookingClass' => $segment['BookingClass'],
                    'status' => $segment['Status']
                ];
            }, $itinerary['Segments'] ?? [])
        ];
    }

    private function parseTravelers(array $travelers): void
    {
        $this->travelers = array_map(function ($traveler) {
            return [
                'id' => $traveler['id'],
                'givenName' => $traveler['GivenName'],
                'surname' => $traveler['Surname'],
                'passengerCode' => $traveler['PassengerCode'],
                'ticketNumber' => $traveler['TicketNumber'] ?? null
            ];
        }, $travelers);
    }

    private function parsePricing(array $pricing): void
    {
        $this->pricing = [
            'totalAmount' => $pricing['TotalAmount'] ?? null,
            'currency' => $pricing['Currency'] ?? null,
            'breakdown' => array_map(function ($item) {
                return [
                    'type' => $item['Type'],
                    'amount' => $item['Amount'],
                    'currency' => $item['Currency']
                ];
            }, $pricing['Breakdown'] ?? [])
        ];
    }
}
