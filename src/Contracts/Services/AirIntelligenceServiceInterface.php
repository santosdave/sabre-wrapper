<?php

namespace Santosdave\Sabre\Contracts\Services;

use Santosdave\Sabre\Models\Intelligence\SeasonalityRequest;
use Santosdave\Sabre\Models\Intelligence\LowFareHistoryRequest;

interface AirIntelligenceServiceInterface
{
    public function getTravelSeasonality(SeasonalityRequest $request): array;
    public function getLowFareHistory(LowFareHistoryRequest $request): array;
}