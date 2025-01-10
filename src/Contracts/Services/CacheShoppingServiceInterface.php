<?php

namespace Santosdave\Sabre\Contracts\Services;

use Santosdave\Sabre\Models\Air\Cache\InstaFlightsRequest;
use Santosdave\Sabre\Models\Air\Cache\DestinationFinderRequest;
use Santosdave\Sabre\Models\Air\Cache\LeadPriceCalendarRequest;

interface CacheShoppingServiceInterface
{
    public function searchInstaFlights(InstaFlightsRequest $request): array;
    public function findDestinations(DestinationFinderRequest $request): array;
    public function getLeadPriceCalendar(LeadPriceCalendarRequest $request): array;
}