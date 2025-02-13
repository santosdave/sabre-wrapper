<?php

namespace Santosdave\SabreWrapper\Services\Rest\Air;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\CacheShoppingServiceInterface;
use Santosdave\SabreWrapper\Models\Air\Cache\InstaFlightsRequest;
use Santosdave\SabreWrapper\Models\Air\Cache\DestinationFinderRequest;
use Santosdave\SabreWrapper\Models\Air\Cache\LeadPriceCalendarRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class CacheShoppingService extends BaseRestService implements CacheShoppingServiceInterface
{
    public function searchInstaFlights(InstaFlightsRequest $request): array
    {
        try {
            $response = $this->client->get('/v1/shop/flights', $request->toArray());
            return $this->normalizeInstaFlightsResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to search InstaFlights: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function findDestinations(DestinationFinderRequest $request): array
    {
        try {
            $response = $this->client->get('/v2/shop/flights/fares', $request->toArray());
            return $this->normalizeDestinationFinderResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to find destinations: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getLeadPriceCalendar(LeadPriceCalendarRequest $request): array
    {
        try {
            $response = $this->client->get('/v2/shop/flights/fares', $request->toArray());
            return $this->normalizeLeadPriceResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get lead prices: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    private function normalizeInstaFlightsResponse(array $response): array
    {
        if (!isset($response['PricedItineraries'])) {
            return [];
        }

        $itineraries = array_map(function ($itinerary) {
            return [
                'sequence_number' => $itinerary['SequenceNumber'] ?? null,
                'itinerary_type' => $itinerary['AirItinerary']['DirectionInd'] ?? null,
                'total_fare' => [
                    'amount' => $itinerary['AirItineraryPricingInfo']['ItinTotalFare']['TotalFare']['Amount'],
                    'currency' => $itinerary['AirItineraryPricingInfo']['ItinTotalFare']['TotalFare']['CurrencyCode']
                ],
                'fare_info' => $itinerary['AirItineraryPricingInfo'] ?? null,
                'segments' => $this->extractSegments($itinerary['AirItinerary']['OriginDestinationOptions']),
                'validating_carrier' => $itinerary['TPA_Extensions']['ValidatingCarrier']['Code'] ?? null,
                'ticketing_info' => $itinerary['TicketingInfo'] ?? null,
                'last_ticketing_date' => $itinerary['AirItineraryPricingInfo']['LastTicketDate'] ?? null,
            ];
        }, $response['PricedItineraries']);

        return [
            'origin' => $response['OriginLocation'] ?? null,
            'destination' => $response['DestinationLocation'] ?? null,
            'departure_date' => $response['DepartureDateTime'] ?? null,
            'arrival_date' => $response['ReturnDateTime'] ?? null,
            'links' => $response['Links'] ?? null,
            'itineraries' => $itineraries
        ];
    }

    private function normalizeDestinationFinderResponse(array $response): array
    {
        if (!isset($response['FareInfo'])) {
            return [];
        }

        $destinations = array_map(function ($fare) {
            return [
                'destination' => $fare['DestinationLocation'],
                'lowest_fare' => [
                    'amount' => $fare['LowestFare']['Fare'] ?? null,
                    'currency' => $fare['CurrencyCode'] ?? null,
                    'airlines' => $fare['LowestFare']['AirlineCodes'] ?? []
                ],
                'lowest_nonstop_fare' => [
                    'amount' => $fare['LowestNonStopFare']['Fare'] ?? null,
                    'currency' => $fare['CurrencyCode'] ?? null,
                    'airlines' => $fare['LowestNonStopFare']['AirlineCodes'] ?? []
                ],
                'distance' => $fare['Distance'] ?? null,
                'price_per_mile' => $fare['PricePerMile'] ?? null,
                'departure_dates' => $fare['DepartureDateTime'] ?? [],
                'return_dates' => $fare['ReturnDateTime'] ?? [],
                'links' => $fare['Links'] ?? []
            ];
        }, $response['FareInfo']);

        return [
            'origin' => $response['OriginLocation'] ?? null,
            'links' => $response['Links'] ?? null,
            'destinations' => $destinations
        ];
    }

    private function normalizeLeadPriceResponse(array $response): array
    {
        if (!isset($response['FareInfo'])) {
            return [];
        }

        $calendars = array_map(function ($fare) {
            return [
                'departure_date' => $fare['DepartureDateTime'],
                'return_date' => $fare['ReturnDateTime'] ?? null,
                'lowest_fare' => [
                    'amount' => $fare['LowestFare']['Fare'] ?? null,
                    'currency' => $fare['CurrencyCode'] ?? null,
                    'airlines' => $fare['LowestFare']['AirlineCodes'] ?? []
                ],
                'lowest_nonstop_fare' => [
                    'amount' => $fare['LowestNonStopFare']['Fare'] ?? null,
                    'currency' => $fare['CurrencyCode'] ?? null,
                    'airlines' => $fare['LowestNonStopFare']['AirlineCodes'] ?? []
                ],
                'links' => $fare['Links'] ?? []
            ];
        }, $response['FareInfo']);

        return [
            'origin' => $response['OriginLocation'] ?? null,
            'destination' => $response['DestinationLocation'] ?? null,
            'links' => $response['Links'] ?? null,
            'calendars' => $calendars
        ];
    }

    private function extractSegments(array $options): array
    {
        $segments = [];
        foreach ($options['OriginDestinationOption'] as $option) {
            $flightSegments = $option['FlightSegment'] ?? [];
            if (!is_array($flightSegments)) {
                continue;
            }

            foreach ($flightSegments as $segment) {
                $segments[] = [
                    'departure' => [
                        'airport' => $segment['DepartureAirport']['LocationCode'],
                        'time' => $segment['DepartureDateTime'],
                        'timezone' => $segment['DepartureTimeZone']['GMTOffset'] ?? null,
                    ],
                    'arrival' => [
                        'airport' => $segment['ArrivalAirport']['LocationCode'],
                        'time' => $segment['ArrivalDateTime'],
                        'timezone' => $segment['ArrivalTimeZone']['GMTOffset'] ?? null,
                    ],
                    'flight_details' => [
                        'flight_number' => $segment['FlightNumber'] ?? null,
                        'marketing_carrier' => $segment['MarketingAirline']['Code'] ?? null,
                        'operating_carrier' => $segment['OperatingAirline']['Code'] ?? null,
                        'equipment' => $segment['Equipment']['AirEquipType'] ?? null,
                        'res_book_designator_code' => $segment['ResBookDesigCode'],
                    ],
                    'duration' => $segment['ElapsedTime'] ?? null,
                    'tpa_extensions' => $segment['TPA_Extensions'] ?? null,
                    'stop_quantity' => $segment['StopQuantity'] ?? null,
                    'elapsed_time' =>  $segment['ElapsedTime'] ?? null,
                    'marriage_group' => $segment['MarriageGrp'] ?? null,
                ];
            }
        }
        return $segments;
    }
}
