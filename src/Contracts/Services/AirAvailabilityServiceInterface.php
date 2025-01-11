<?php

namespace Santosdave\SabreWrapper\Contracts\Services;

use Santosdave\SabreWrapper\Models\Air\AvailabilityRequest;
use Santosdave\SabreWrapper\Models\Air\AvailabilityResponse;

interface AirAvailabilityServiceInterface
{
    public function getAvailability(AvailabilityRequest $request): AvailabilityResponse;
    public function getSchedules(AvailabilityRequest $request): AvailabilityResponse;
}
