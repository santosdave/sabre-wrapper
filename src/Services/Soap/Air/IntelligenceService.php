<?php

namespace Santosdave\SabreWrapper\Services\Soap\Air;

use Santosdave\SabreWrapper\Services\Base\BaseSoapService;
use Santosdave\SabreWrapper\Contracts\Services\AirIntelligenceServiceInterface;
use Santosdave\SabreWrapper\Models\Intelligence\SeasonalityRequest;
use Santosdave\SabreWrapper\Models\Intelligence\LowFareHistoryRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class IntelligenceService extends BaseSoapService implements AirIntelligenceServiceInterface
{
    public function getTravelSeasonality(SeasonalityRequest $request): array
    {
        throw new SabreApiException('Travel Seasonality is only available via REST API');
    }

    public function getLowFareHistory(LowFareHistoryRequest $request): array
    {
        throw new SabreApiException('Low Fare History is only available via REST API');
    }
}
