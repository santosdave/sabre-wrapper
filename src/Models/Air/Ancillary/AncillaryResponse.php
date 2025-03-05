<?php

namespace Santosdave\SabreWrapper\Models\Air\Ancillary;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class AncillaryResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;

    // Detailed response components
    private array $segments = [];
    private array $passengers = [];
    private ?array $offer = null;
    private array $serviceDefinitions = [];
    private array $priceDefinitions = [];
    private array $warnings = [];

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

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getPassengers(): array
    {
        return $this->passengers;
    }

    public function getOffer(): ?array
    {
        return $this->offer;
    }

    public function getServiceDefinitions(): array
    {
        return $this->serviceDefinitions;
    }

    public function getPriceDefinitions(): array
    {
        return $this->priceDefinitions;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        // Check for top-level errors
        if (isset($response['errors']) && !empty($response['errors'])) {
            $this->success = false;
            $this->errors = $this->parseErrors($response['errors']);
            return;
        }

        // Check for NDC ancillary response
        $ancillaryResponse = $response['response']['ancillaries'] ?? $response['ancillaries'] ?? $response;

        // Determine success
        $this->success = isset($ancillaryResponse['serviceDefinitions']);

        // Parse warnings
        if (isset($response['warnings'])) {
            $this->warnings = $this->parseWarnings($response['warnings']);
        }

        // Parse offer
        if (isset($ancillaryResponse['offer'])) {
            $this->offer = $this->parseOffer($ancillaryResponse['offer']);
        }

        // Parse segments
        if (isset($ancillaryResponse['segments'])) {
            $this->segments = $this->parseSegments($ancillaryResponse['segments']);
        }

        // Parse passengers
        if (isset($ancillaryResponse['passengers'])) {
            $this->passengers = $this->parsePassengers($ancillaryResponse['passengers']);
        }

        // Parse service definitions
        if (isset($ancillaryResponse['serviceDefinitions'])) {
            $this->serviceDefinitions = $this->parseServiceDefinitions($ancillaryResponse['serviceDefinitions']);
        }

        // Parse price definitions
        if (isset($ancillaryResponse['priceDefinitions'])) {
            $this->priceDefinitions = $this->parsePriceDefinitions($ancillaryResponse['priceDefinitions']);
        }
    }

    private function parseErrors(array $errors): array
    {
        return array_map(function ($error) {
            return [
                'code' => $error['code'] ?? null,
                'description' => $error['descriptionText'] ?? 'Unknown error',
                'type' => $error['typeCode'] ?? null,
                'owner' => $error['ownerName'] ?? null,
                'url' => $error['url'] ?? null
            ];
        }, $errors);
    }

    private function parseWarnings(array $warnings): array
    {
        return array_map(function ($warning) {
            return [
                'code' => $warning['code'] ?? null,
                'description' => $warning['descriptionText'] ?? null,
                'owner' => $warning['ownerName'] ?? null
            ];
        }, $warnings);
    }

    private function parseOffer(array $offer): array
    {
        return [
            'id' => $offer['offerId'] ?? null,
            'other_services' => $this->parseOfferItems($offer['otherServices'] ?? [])
        ];
    }

    private function parseOfferItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'id' => $item['offerItemId'] ?? null,
                'service_definition_ref' => $item['serviceDefinitionRef'] ?? null,
                'price_definition_ref' => $item['priceDefinitionRef'] ?? null,
                'segment_refs' => $item['segmentRefs'] ?? [],
                'passenger_refs' => $item['passengerRefs'] ?? []
            ];
        }, $items);
    }

    private function parseSegments(array $segments): array
    {
        return array_map(function ($segment) {
            return [
                'id' => $segment['id'] ?? null,
                'booking_airline_code' => $segment['bookingAirlineCode'] ?? null,
                'booking_flight_number' => $segment['bookingFlightNumber'] ?? null,
                'departure_airport_code' => $segment['departureAirportCode'] ?? null,
                'arrival_airport_code' => $segment['arrivalAirportCode'] ?? null,
                'departure_date' => $segment['departureDate'] ?? null,
                'operating_airline_code' => $segment['operatingAirlineCode'] ?? null,
                'booking_class_code' => $segment['bookingClassCode'] ?? null
            ];
        }, $segments);
    }

    private function parsePassengers(array $passengers): array
    {
        return array_map(function ($passenger) {
            return [
                'id' => $passenger['passengerId'] ?? null,
                'type_code' => $passenger['passengerTypeCode'] ?? null,
                'title' => $passenger['title'] ?? null,
                'given_name' => $passenger['givenName'] ?? null,
                'surname' => $passenger['surname'] ?? null,
                'middle_name' => $passenger['middleName'] ?? null,
                'suffix_name' => $passenger['suffixName'] ?? null
            ];
        }, $passengers);
    }

    private function parseServiceDefinitions(array $serviceDefinitions): array
    {
        return array_map(function ($service) {
            return [
                'id' => $service['id'] ?? null,
                'service_code' => $service['serviceCode'] ?? null,
                'airline_code' => $service['airlineCode'] ?? null,
                'commercial_name' => $service['commercialName'] ?? null,
                'group_code' => $service['groupCode'] ?? null,
                'owner_code' => $service['ownerCode'] ?? null,
                'maximum_quantity' => $service['maximumQuantity'] ?? null,
                'booking_method' => $service['bookingMethod'] ?? null,
                'cabin_upgrade' => $this->parseCabinUpgrade($service['cabinUpgrade'] ?? null),
                'description_text' => $this->parseDescriptionText($service['descriptionFreeText'] ?? [])
            ];
        }, $serviceDefinitions);
    }

    private function parseCabinUpgrade(?array $cabinUpgrade): ?array
    {
        if (!$cabinUpgrade) {
            return null;
        }

        return [
            'method_code' => $cabinUpgrade['methodCode'] ?? null,
            'reservation_booking_designator' => $cabinUpgrade['reservationBookingDesignator'] ?? null
        ];
    }

    private function parseDescriptionText(array $descriptions): array
    {
        return array_map(function ($description) {
            return [
                'id' => $description['id'] ?? null,
                'text' => $description['text'] ?? null
            ];
        }, $descriptions);
    }

    private function parsePriceDefinitions(array $priceDefinitions): array
    {
        return array_map(function ($priceDefinition) {
            return [
                'id' => $priceDefinition['id'] ?? null,
                'service_fee' => $this->parseServiceFee($priceDefinition['serviceFee'] ?? null)
            ];
        }, $priceDefinitions);
    }

    private function parseServiceFee(?array $serviceFee): ?array
    {
        if (!$serviceFee) {
            return null;
        }

        return [
            'unit_price' => $this->parsePriceElement($serviceFee['unitPrice'] ?? null),
            'total_price' => $this->parsePriceElement($serviceFee['totalPrice'] ?? null)
        ];
    }

    private function parsePriceElement(?array $priceElement): ?array
    {
        if (!$priceElement) {
            return null;
        }

        return [
            'sale_amount' => [
                'amount' => $priceElement['saleAmount']['amount'] ?? null,
                'currency' => $priceElement['saleAmount']['currencyCode'] ?? null
            ],
            'tax_summary' => $this->parseTaxSummary($priceElement['taxSummary'] ?? null)
        ];
    }

    private function parseTaxSummary(?array $taxSummary): ?array
    {
        if (!$taxSummary) {
            return null;
        }

        return [
            'total_taxes' => [
                'amount' => $taxSummary['taxesTotal']['amount'] ?? null,
                'currency' => $taxSummary['taxesTotal']['currencyCode'] ?? null
            ],
            'taxes' => $this->parseTaxes($taxSummary['taxes'] ?? []),
            'is_tax_exempt' => $taxSummary['isTaxExempt'] ?? false
        ];
    }

    private function parseTaxes(array $taxes): array
    {
        return array_map(function ($tax) {
            return [
                'amount' => $tax['taxAmount']['amount'] ?? null,
                'currency' => $tax['taxAmount']['currencyCode'] ?? null,
                'tax_code' => $tax['taxCode'] ?? null,
                'description' => $tax['taxDescription'] ?? null
            ];
        }, $taxes);
    }

    // Convenience method to categorize ancillaries
    public function categorizeAncillaries(): array
    {
        $categories = [
            'baggage' => [],
            'meals' => [],
            'special_services' => [],
            'seat_upgrades' => [],
            'other' => []
        ];

        foreach ($this->serviceDefinitions as $service) {
            switch ($service['service_code']) {
                case 'B': // Baggage
                    $categories['baggage'][] = $service;
                    break;
                case 'M': // Meals
                    $categories['meals'][] = $service;
                    break;
                case 'S': // Special Services
                    $categories['special_services'][] = $service;
                    break;
                case 'U': // Seat Upgrades
                    $categories['seat_upgrades'][] = $service;
                    break;
                default:
                    $categories['other'][] = $service;
            }
        }

        return $categories;
    }

    // Method to retrieve available ancillary services for a specific passenger
    public function getPassengerAncillaries(string $passengerId): array
    {
        $passengerAncillaries = [];

        // Find offer items for the specified passenger
        $passengerOfferItems = array_filter(
            $this->offer['other_services'] ?? [],
            function ($item) use ($passengerId) {
                return in_array($passengerId, $item['passenger_refs'] ?? []);
            }
        );

        // Match offer items with service definitions
        foreach ($passengerOfferItems as $offerItem) {
            $serviceDefinition = $this->findServiceDefinitionById(
                $offerItem['service_definition_ref']
            );

            if ($serviceDefinition) {
                $priceDefinition = $this->findPriceDefinitionById(
                    $offerItem['price_definition_ref']
                );

                $passengerAncillaries[] = [
                    'service' => $serviceDefinition,
                    'price' => $priceDefinition,
                    'offer_item_id' => $offerItem['id'],
                    'segment_refs' => $offerItem['segment_refs']
                ];
            }
        }

        return $passengerAncillaries;
    }

    // Helper method to find service definition by ID
    private function findServiceDefinitionById(?string $id): ?array
    {
        if (!$id) {
            return null;
        }

        foreach ($this->serviceDefinitions as $service) {
            if ($service['id'] === $id) {
                return $service;
            }
        }

        return null;
    }

    // Helper method to find price definition by ID
    private function findPriceDefinitionById(?string $id): ?array
    {
        if (!$id) {
            return null;
        }

        foreach ($this->priceDefinitions as $price) {
            if ($price['id'] === $id) {
                return $price;
            }
        }

        return null;
    }
}
