<?php

namespace Santosdave\SabreWrapper\Models\Air;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class AvailabilityResponse implements SabreResponse
{
    private array $data;
    private array $errors = [];
    private bool $success = false;
    private array $flights = [];

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

    public function getFlights(): array
    {
        return $this->flights;
    }

    private function parseResponse(array $response, string $type): void
    {
        if ($type === 'rest') {
            $this->parseRestResponse($response);
        } else {
            $this->parseSoapResponse($response);
        }
    }

    private function parseRestResponse(array $response): void
    {
        if (isset($response['AirSchedulesAndAvailabilityRS'])) {
            $this->success = true;
            $this->data = $response;

            if (isset($response['AirSchedulesAndAvailabilityRS']['OriginAndDestination'])) {
                foreach ($response['AirSchedulesAndAvailabilityRS']['OriginAndDestination'] as $flight) {
                    $this->flights[] = $this->normalizeFlightData($flight);
                }
            }
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid REST response format';
        }
    }

    private function parseSoapResponse(array $response): void
    {
        if (isset($response['OTA_AirAvailRS'])) {
            $this->success = true;
            $this->data = $response;

            if (isset($response['OTA_AirAvailRS']['OriginDestinationOptions']['OriginDestinationOption'])) {
                foreach ($response['OTA_AirAvailRS']['OriginDestinationOptions']['OriginDestinationOption'] as $option) {
                    $this->flights[] = $this->normalizeFlightData($option);
                }
            }
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid SOAP response format';
        }
    }

    private function normalizeFlightData(array $flight): array
    {
        // Common structure for both REST and SOAP responses
        return [
            'departure_airport' => $flight['Flight']['DepartureAirport'] ?? $flight['DepartureAirport']['LocationCode'] ?? null,
            'arrival_airport' => $flight['Flight']['ArrivalAirport'] ?? $flight['ArrivalAirport']['LocationCode'] ?? null,
            'departure_time' => $flight['Flight']['DepartureDateTime'] ?? $flight['DepartureDateTime'] ?? null,
            'arrival_time' => $flight['Flight']['ArrivalDateTime'] ?? $flight['ArrivalDateTime'] ?? null,
            'flight_number' => $flight['Flight']['FlightNumber'] ?? $flight['FlightNumber'] ?? null,
            'carrier' => $flight['Flight']['MarketingAirline'] ?? $flight['MarketingAirline']['Code'] ?? null,
            'equipment' => $flight['Flight']['Equipment'] ?? $flight['Equipment']['AirEquipType'] ?? null,
            'cabin_class' => $flight['Flight']['BookingClass'] ?? $flight['BookingClassAvail'] ?? null,
            'seats_available' => $flight['Flight']['SeatsAvailable'] ?? $flight['SeatsAvailable'] ?? null,
        ];
    }
}
