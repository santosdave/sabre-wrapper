<?php

namespace Santosdave\Sabre\Models\Air;

use Santosdave\Sabre\Contracts\SabreResponse;

class BargainFinderMaxResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private array $offers = [];
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

    public function getOffers(): array
    {
        return $this->offers;
    }

    public function getSummary(): ?array
    {
        return $this->summary;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        if (isset($response['OTA_AirLowFareSearchRS'])) {
            $this->parseSearchResponse($response['OTA_AirLowFareSearchRS']);
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid response format';
        }
    }

    private function parseSearchResponse(array $response): void
    {
        if (isset($response['Errors'])) {
            $this->success = false;
            $this->errors = $this->parseErrors($response['Errors']);
            return;
        }

        $this->success = true;

        if (isset($response['PricedItineraries'])) {
            $this->parseOffers($response['PricedItineraries']);
        }

        if (isset($response['TPA_Extensions']['Statistics'])) {
            $this->parseSummary($response['TPA_Extensions']['Statistics']);
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

    private function parseOffers(array $pricedItineraries): void
    {
        foreach ($pricedItineraries as $itinerary) {
            $this->offers[] = [
                'itinerary' => $this->parseItinerary($itinerary['AirItinerary'] ?? []),
                'pricing' => $this->parsePricing($itinerary['AirItineraryPricingInfo'] ?? []),
                'source' => $itinerary['TPA_Extensions']['Source'] ?? null,
                'offer_id' => $itinerary['TPA_Extensions']['NDC']['OfferID'] ?? null,
                'offer_item_id' => $itinerary['TPA_Extensions']['NDC']['OfferItemID'] ?? null
            ];
        }
    }

    private function parseItinerary(array $itinerary): array
    {
        $segments = [];
        if (isset($itinerary['OriginDestinationOptions']['OriginDestinationOption'])) {
            foreach ($itinerary['OriginDestinationOptions']['OriginDestinationOption'] as $option) {
                $segments[] = array_map(function ($segment) {
                    return [
                        'departure' => [
                            'airport' => $segment['DepartureAirport']['LocationCode'],
                            'terminal' => $segment['DepartureAirport']['TerminalID'] ?? null,
                            'time' => $segment['DepartureDateTime']
                        ],
                        'arrival' => [
                            'airport' => $segment['ArrivalAirport']['LocationCode'],
                            'terminal' => $segment['ArrivalAirport']['TerminalID'] ?? null,
                            'time' => $segment['ArrivalDateTime']
                        ],
                        'marketing_carrier' => [
                            'code' => $segment['MarketingAirline']['Code'],
                            'flight_number' => $segment['FlightNumber']
                        ],
                        'operating_carrier' => [
                            'code' => $segment['OperatingAirline']['Code'] ?? $segment['MarketingAirline']['Code'],
                            'flight_number' => $segment['OperatingAirline']['FlightNumber'] ?? $segment['FlightNumber']
                        ],
                        'equipment' => $segment['Equipment']['AirEquipType'] ?? null,
                        'cabin' => $segment['ResBookDesigCode'],
                        'duration' => $segment['ElapsedTime'] ?? null,
                        'stops' => $segment['StopQuantity'] ?? 0,
                        'disclosure' => $segment['Disclosure'] ?? null
                    ];
                }, (array) $option['FlightSegment']);
            }
        }
        return $segments;
    }

    private function parsePricing(array $pricingInfo): array
    {
        return [
            'fare' => [
                'currency' => $pricingInfo['ItinTotalFare']['TotalFare']['CurrencyCode'] ?? null,
                'amount' => $pricingInfo['ItinTotalFare']['TotalFare']['Amount'] ?? null,
                'base_amount' => $pricingInfo['ItinTotalFare']['BaseFare']['Amount'] ?? null,
                'tax_amount' => $pricingInfo['ItinTotalFare']['Taxes']['TotalAmount'] ?? null
            ],
            'passenger_totals' => array_map(function ($paxType) {
                return [
                    'code' => $paxType['PassengerTypeQuantity']['Code'],
                    'quantity' => $paxType['PassengerTypeQuantity']['Quantity'],
                    'total' => $paxType['PassengerFare']['TotalFare']['Amount'] ?? null
                ];
            }, (array) ($pricingInfo['PTC_FareBreakdowns']['PTC_FareBreakdown'] ?? [])),
            'validating_carrier' => $pricingInfo['ValidatingCarrierCode'] ?? null,
            'fare_type' => $pricingInfo['FareType'] ?? null
        ];
    }

    private function parseSummary(array $statistics): void
    {
        $this->summary = [
            'itineraries_found' => $statistics['ItinerariesFound'] ?? 0,
            'itineraries_returned' => $statistics['ItinerariesReturned'] ?? 0,
            'lowest_fare' => $statistics['LowestFare'] ?? null,
            'execution_time' => $statistics['ExecutionTime'] ?? null,
            'sources' => [
                'ndc' => $statistics['NDCSourcesParsed'] ?? 0,
                'atpco' => $statistics['ATPCOSourcesParsed'] ?? 0,
                'lcc' => $statistics['LCCSourcesParsed'] ?? 0
            ]
        ];
    }
}