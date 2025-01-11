# Availability and Cache Shopping Service Documentation

## Availability Service

### Overview

The Availability Service provides flight availability information through both REST and SOAP endpoints, supporting real-time availability checks and schedule retrieval.

### Features

- Real-time availability checks
- Schedule retrieval
- Support for multiple carriers
- Cabin-specific availability
- Connection availability

### Implementation

```php
interface AirAvailabilityServiceInterface
{
    public function getAvailability(AvailabilityRequest $request): AvailabilityResponse;
    public function getSchedules(AvailabilityRequest $request): AvailabilityResponse;
}

class AvailabilityService extends BaseRestService implements AirAvailabilityServiceInterface
{
    public function getAvailability(AvailabilityRequest $request): AvailabilityResponse
    {
        try {
            $response = $this->client->post(
                '/v5.3.0/shop/flights/availability',
                $request->toRestArray()
            );
            return new AvailabilityResponse($response, 'rest');
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to get availability: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getSchedules(AvailabilityRequest $request): AvailabilityResponse
    {
        try {
            $response = $this->client->post(
                '/v5.3.0/shop/flights/schedules',
                $request->toRestArray()
            );
            return new AvailabilityResponse($response, 'rest');
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to get schedules: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
```

### Usage Examples

```php
// Check availability
$request = new AvailabilityRequest();
$request
    ->setOrigin('JFK')
    ->setDestination('LHR')
    ->setDepartureDate('2024-03-15');

$availability = Sabre::availability()->getAvailability($request);

// Get schedules
$request = new AvailabilityRequest();
$request
    ->setOrigin('JFK')
    ->setDestination('LHR')
    ->setDateRange('2024-03-15', '2024-03-20');

$schedules = Sabre::availability()->getSchedules($request);
```

### Error Handling

```php
try {
    $availability = $service->getAvailability($request);
} catch (SabreApiException $e) {
    if ($e->getCode() === 'INVALID_FLIGHT_NUMBER') {
        // Handle invalid flight number
    } elseif ($e->getCode() === 'NO_AVAILABILITY') {
        // Handle no availability
    } else {
        // Handle other errors
    }
}
```

## Cache Shopping Service

### Overview

The Cache Shopping Service provides access to cached flight search results for faster response times and reduced API calls.

### Features

- InstaFlights search
- Destination finder
- Lead price calendar
- Cached results
- Configurable cache duration

### Implementation

```php
interface CacheShoppingServiceInterface
{
    public function searchInstaFlights(InstaFlightsRequest $request): array;
    public function findDestinations(DestinationFinderRequest $request): array;
    public function getLeadPriceCalendar(LeadPriceCalendarRequest $request): array;
}

class CacheShoppingService extends BaseRestService implements CacheShoppingServiceInterface
{
    public function searchInstaFlights(InstaFlightsRequest $request): array
    {
        try {
            $response = $this->client->get(
                '/v1/shop/flights',
                $request->toArray()
            );
            return $this->normalizeInstaFlightsResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to search InstaFlights: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    private function normalizeInstaFlightsResponse(array $response): array
    {
        if (!isset($response['PricedItineraries'])) {
            return [];
        }

        return array_map(function ($itinerary) {
            return [
                'total_fare' => [
                    'amount' => $itinerary['AirItineraryPricingInfo']['ItinTotalFare']['TotalFare']['Amount'],
                    'currency' => $itinerary['AirItineraryPricingInfo']['ItinTotalFare']['TotalFare']['CurrencyCode']
                ],
                'segments' => $this->extractSegments($itinerary['AirItinerary']['OriginDestinationOptions']),
                'validating_carrier' => $itinerary['AirItineraryPricingInfo']['ValidatingCarrierCode'] ?? null
            ];
        }, $response['PricedItineraries']);
    }
}
```

### Usage Examples

```php
// InstaFlights search
$request = new InstaFlightsRequest(
    'JFK',
    'LHR',
    '2024-03-15',
    'US'
);
$request->setLimit(50);

$flights = Sabre::cacheShopping()->searchInstaFlights($request);

// Destination finder
$request = new DestinationFinderRequest('JFK', 'US');
$request->setMaxFare(1000);

$destinations = Sabre::cacheShopping()->findDestinations($request);

// Lead price calendar
$request = new LeadPriceCalendarRequest(
    'JFK',
    'LHR',
    'US'
);
$request->setDateRange('2024-03-01', '2024-03-31');

$prices = Sabre::cacheShopping()->getLeadPriceCalendar($request);
```

### Cache Configuration

```php
// config/sabre.php
return [
    'cache' => [
        'enabled' => env('SABRE_CACHE_ENABLED', true),
        'ttl' => [
            'insta_flights' => 300,      // 5 minutes
            'destinations' => 3600,       // 1 hour
            'lead_prices' => 1800        // 30 minutes
        ],
        'prefix' => 'sabre_cache_',
        'store' => 'redis'
    ]
];
```

### Cache Implementation

```php
trait CacheableRequest
{
    private function withCache(string $key, callable $callback, ?string $type = null): mixed
    {
        if (!$this->config['enabled']) {
            return $callback();
        }

        $cacheKey = $this->generateCacheKey($key);
        $cacheTTL = $this->getTTL($type);

        try {
            return Cache::remember($cacheKey, $cacheTTL, function () use ($callback, $key, $type) {
                $result = $callback();
                $this->logCacheOperation('set', $key, $type);
                return $result;
            });
        } catch (\Exception $e) {
            Log::error('Cache operation failed', [
                'key' => $key,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return $callback();
        }
    }

    private function getTTL(?string $type): int
    {
        return $this->config['ttl'][$type] ?? $this->config['ttl']['default'];
    }
}
```

### Best Practices

1. Cache Invalidation

```php
class CacheInvalidationService
{
    public function invalidateByPattern(string $pattern): void
    {
        $store = Cache::getStore();
        $keys = $this->getCacheKeys($pattern);

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    public function invalidateDestinationCache(string $origin): void
    {
        $this->invalidateByPattern("destinations_{$origin}_*");
    }
}
```

2. Cache Warmup

```php
class CacheWarmupJob implements ShouldQueue
{
    public function handle()
    {
        $popularRoutes = config('sabre.cache.warmup.popular_routes', []);

        foreach ($popularRoutes as $route) {
            $request = new InstaFlightsRequest(
                $route['origin'],
                $route['destination'],
                now()->addDays(7)->format('Y-m-d'),
            $route['pointOfSale']
        );

        try {
            Sabre::cacheShopping()->searchInstaFlights($request);
            Log::info('Cache warmed up for route', [
                'origin' => $route['origin'],
                'destination' => $route['destination']
            ]);
        } catch (\Exception $e) {
            Log::error('Cache warmup failed', [
                'route' => $route,
                'error' => $e->getMessage()
            ]);
        }

        // Avoid rate limiting
        sleep(config('sabre.cache.warmup.delay', 2));
    }
}

```

## Intelligence Service

### Overview

The Intelligence Service provides access to travel analytics and historical data.

### Features

- Travel seasonality analysis
- Low fare history
- Market demand insights
- Pricing trends
- Historical performance

### Implementation

```php
interface AirIntelligenceServiceInterface
{
    public function getTravelSeasonality(SeasonalityRequest $request): array;
    public function getLowFareHistory(LowFareHistoryRequest $request): array;
}

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

    private function normalizeSeasonalityResponse(array $response): array
    {
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
}
```
### Usage Examples

```php
// Get travel seasonality
$request = new SeasonalityRequest('LHR');
$request->setOrigin('JFK');
$seasonality = Sabre::intelligence()->getTravelSeasonality($request);

// Get low fare history
$request = new LowFareHistoryRequest(
    'JFK',
    'LHR',
    '2024-03-15',
    'US'
);
$history = Sabre::intelligence()->getLowFareHistory($request);
```

## Utility Service

### Overview

The Utility Service provides access to reference data and utility functions.

### Features

- Airport information
- Airline information
- Aircraft information
- Geographic search
- Currency conversion

### Implementation

```php
interface UtilityServiceInterface
{
    public function getAirportsAtCity(string $cityCode): array;
    public function getAirlineInfo(string $airlineCode): array;
    public function getAirlineAlliance(string $allianceCode): array;
    public function getAircraftInfo(string $aircraftCode): array;
    public function geoSearch(
        string $searchTerm,
        string $category,
        ?int $radius = null,
        ?string $unit = null
    ): array;
}

class UtilityService extends BaseRestService implements UtilityServiceInterface
{
    public function geoSearch(
        string $searchTerm,
        string $category,
        ?int $radius = null,
        ?string $unit = null
    ): array {
        try {
            $params = array_filter([
                'term' => $searchTerm,
                'category' => $category,
                'radius' => $radius,
                'unit' => $unit
            ]);

            return $this->client->get('/v1/lists/utilities/geosearch', $params);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to perform geo search: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
```

### Usage Examples

```php
// Geo search
$airports = Sabre::utility()->geoSearch(
    'London',
    'AIRPORT',
    50,
    'KM'
);

// Get airline info
$airline = Sabre::utility()->getAirlineInfo('BA');

// Get aircraft info
$aircraft = Sabre::utility()->getAircraftInfo('788');
```

### Error Handling

```php
try {
    $results = $service->geoSearch($term, $category);
} catch (SabreApiException $e) {
    if ($e->getCode() === 'INVALID_CATEGORY') {
        // Handle invalid category
    } elseif ($e->getCode() === 'NO_RESULTS') {
        // Handle no results
    } else {
        // Handle other errors
    }
}
```
