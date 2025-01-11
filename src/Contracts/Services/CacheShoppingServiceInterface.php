<?php

namespace Santosdave\SabreWrapper\Contracts\Services;

use Santosdave\SabreWrapper\Models\Air\Cache\InstaFlightsRequest;
use Santosdave\SabreWrapper\Models\Air\Cache\DestinationFinderRequest;
use Santosdave\SabreWrapper\Models\Air\Cache\LeadPriceCalendarRequest;

interface CacheShoppingServiceInterface
{
    public function searchInstaFlights(InstaFlightsRequest $request): array;
    public function findDestinations(DestinationFinderRequest $request): array;
    public function getLeadPriceCalendar(LeadPriceCalendarRequest $request): array;
}
