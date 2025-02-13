<?php

namespace Santosdave\SabreWrapper\Services\Rest\Air;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\AirIntelligenceServiceInterface;
use Santosdave\SabreWrapper\Models\Intelligence\SeasonalityRequest;
use Santosdave\SabreWrapper\Models\Intelligence\LowFareHistoryRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

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
        if (!isset($response)) {
            return [];
        }

        return $response;
    }

    private function normalizeFareHistoryResponse(array $response): array
    {
        if (!isset($response)) {
            return [];
        }

        return $response;
    }
}
