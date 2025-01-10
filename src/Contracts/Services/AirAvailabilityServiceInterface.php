<?php

namespace Santosdave\Sabre\Contracts\Services;

use Santosdave\Sabre\Models\Air\AvailabilityRequest;
use Santosdave\Sabre\Models\Air\AvailabilityResponse;

interface AirAvailabilityServiceInterface
{
    public function getAvailability(AvailabilityRequest $request): AvailabilityResponse;
    public function getSchedules(AvailabilityRequest $request): AvailabilityResponse;
}