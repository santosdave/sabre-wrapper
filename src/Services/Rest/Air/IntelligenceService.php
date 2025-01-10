<?php

namespace Santosdave\Sabre\Services\Rest\Air;

use Santosdave\Sabre\Services\Base\BaseRestService;
use Santosdave\Sabre\Contracts\Services\AirIntelligenceServiceInterface;
use Santosdave\Sabre\Models\Intelligence\SeasonalityRequest;
use Santosdave\Sabre\Models\Intelligence\LowFareHistoryRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class IntelligenceService extends BaseRestService implements AirIntelligenceServiceInterface
{
    public function getTravelSeasonality(SeasonalityRequest $request): array
    {
        try {
            $response = $this->client->get(
                "/v1/historical/flights/{$request->toArray()['destination']}/seasonality",
                $request->toArray()
            );

            return $this->normalizeSeasonalityResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get travel seasonality: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getLowFareHistory(LowFareHistoryRequest $request): array
    {
        try {
            $response = $this->client->get(
                '/v1/historical/shop/flights/fares',
                $request->toArray()
            );

            return $this->normalizeFareHistoryResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get low fare history: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    private function normalizeSeasonalityResponse(array $response): array
    {
        if (!isset($response['SeasonalityResponse'])) {
            return [];
        }

        return array_map(function ($month) {
            return [
                'month' => $month['Month'],
                'score' => $month['Score'],
                'demand' => $month['Demand'] ?? null,
                'price' => $month['Price'] ?? null,
                'temperature' => $month['Temperature'] ?? null,
                'precipitation' => $month['Precipitation'] ?? null
            ];
        }, $response['SeasonalityResponse']['Month'] ?? []);
    }

    private function normalizeFareHistoryResponse(array $response): array
    {
        if (!isset($response['FareHistoryResponse'])) {
            return [];
        }

        return [
            'lowest_fare' => $response['FareHistoryResponse']['LowestFare'] ?? null,
            'highest_fare' => $response['FareHistoryResponse']['HighestFare'] ?? null,
            'average_fare' => $response['FareHistoryResponse']['AverageFare'] ?? null,
            'current_fare' => $response['FareHistoryResponse']['CurrentFare'] ?? null,
            'fare_trends' => array_map(function ($trend) {
                return [
                    'date' => $trend['Date'],
                    'fare' => $trend['Fare'],
                    'trend' => $trend['Trend'] ?? null
                ];
            }, $response['FareHistoryResponse']['FareTrend'] ?? [])
        ];
    }
}