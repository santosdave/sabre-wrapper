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

        return array_map(function ($itinerary) {
            return [
                'total_fare' => [
                    'amount' => $itinerary['AirItineraryPricingInfo']['ItinTotalFare']['TotalFare']['Amount'],
                    'currency' => $itinerary['AirItineraryPricingInfo']['ItinTotalFare']['TotalFare']['CurrencyCode']
                ],
                'segments' => $this->extractSegments($itinerary['AirItinerary']['OriginDestinationOptions']),
                'validating_carrier' => $itinerary['AirItineraryPricingInfo']['ValidatingCarrierCode'] ?? null
            ];
        }, $response['PricedItineraries']);
    }

    private function normalizeDestinationFinderResponse(array $response): array
    {
        if (!isset($response['FareInfo'])) {
            return [];
        }

        return array_map(function ($fare) {
            return [
                'destination' => $fare['DestinationLocation'],
                'lowest_fare' => [
                    'amount' => $fare['LowestFare']['Fare'],
                    'currency' => $fare['CurrencyCode']
                ],
                'departure_dates' => $fare['DepartureDates'] ?? [],
                'return_dates' => $fare['ReturnDates'] ?? [],
                'links' => $fare['Links'] ?? []
            ];
        }, $response['FareInfo']);
    }

    private function normalizeLeadPriceResponse(array $response): array
    {
        if (!isset($response['FareInfo'])) {
            return [];
        }

        return array_map(function ($fare) {
            return [
                'departure_date' => $fare['DepartureDateTime'],
                'return_date' => $fare['ReturnDateTime'] ?? null,
                'fare' => [
                    'amount' => $fare['LowestFare']['Fare'],
                    'currency' => $fare['CurrencyCode']
                ],
                'links' => $fare['Links'] ?? []
            ];
        }, $response['FareInfo']);
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
                        'time' => $segment['DepartureDateTime']
                    ],
                    'arrival' => [
                        'airport' => $segment['ArrivalAirport']['LocationCode'],
                        'time' => $segment['ArrivalDateTime']
                    ],
                    'flight_number' => $segment['FlightNumber'],
                    'marketing_carrier' => $segment['MarketingAirline']['Code'],
                    'operating_carrier' => $segment['OperatingAirline']['Code'] ?? null,
                    'duration' => $segment['ElapsedTime'] ?? null,
                    'cabin' => $segment['ResBookDesigCode']
                ];
            }
        }
        return $segments;
    }
}
