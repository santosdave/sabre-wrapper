<?php

namespace Santosdave\SabreWrapper\Contracts\Services;

use Santosdave\SabreWrapper\Models\Intelligence\SeasonalityRequest;
use Santosdave\SabreWrapper\Models\Intelligence\LowFareHistoryRequest;

interface AirIntelligenceServiceInterface
{
    public function getTravelSeasonality(SeasonalityRequest $request): array;
    public function getLowFareHistory(LowFareHistoryRequest $request): array;
}
