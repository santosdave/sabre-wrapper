<?php

namespace Santosdave\Sabre\Services\Soap\Air;

use Santosdave\Sabre\Services\Base\BaseSoapService;
use Santosdave\Sabre\Contracts\Services\AirIntelligenceServiceInterface;
use Santosdave\Sabre\Models\Intelligence\SeasonalityRequest;
use Santosdave\Sabre\Models\Intelligence\LowFareHistoryRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

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