# Shopping Service Documentation

## Overview

The Shopping Service provides access to Sabre's air shopping APIs, including Bargain Finder Max, Alternative Shopping, and Instaflight APIs.

## Service Methods

### bargainFinderMax

Search for flights with full fare and availability information.

```php
use Santosdave\SabreWrapper\Models\Air\BargainFinderMaxRequest;

$request = new BargainFinderMaxRequest();
$request
    ->addOriginDestination('JFK', 'LHR', '2024-03-15')
    ->addTraveler('ADT', 1)
    ->setTravelPreferences([
        'vendorPrefs' => ['AA', 'BA'],
        'cabinPrefs' => ['Y']
    ]);

$response = Sabre::shopping()->bargainFinderMax($request);
```

#### Parameters

- `origin` (string): Origin airport code
- `destination` (string): Destination airport code
- `departureDate` (string): Departure date (YYYY-MM-DD)
- `returnDate` (string, optional): Return date for round trips
- `travelers` (array): Array of traveler types and counts
- `preferences` (array, optional): Search preferences

#### Response

```php
$response->isSuccess(); // bool
$response->getOffers(); // array of offers
$response->getSummary(); // array of search summary
```

### alternativeDatesSearch

Search for flights across multiple dates.

```php
$request = new BargainFinderMaxRequest();
$request
    ->addOriginDestination('JFK', 'LHR', '2024-03-15')
    ->addTraveler('ADT', 1)
    ->setDateFlexibility(3); // +/- 3 days

$response = Sabre::shopping()->alternativeDatesSearch($request);
```

#### Parameters

Same as bargainFinderMax, plus:

- `dateFlexibility` (int): Number of days before/after
- `alternativeDays` (int): Number of alternate results

### instaFlights

Quick search for flight availability.

```php
$results = Sabre::shopping()->instaFlights(
    'JFK',        // origin
    'LHR',        // destination
    '2024-03-15', // departureDate
    null,         // returnDate (optional)
    50           // limit
);
```

## Error Handling

```php
try {
    $response = Sabre::shopping()->bargainFinderMax($request);
} catch (SabreRateLimitException $e) {
    // Handle rate limiting
    $retryAfter = $e->getRetryAfter();
} catch (SabreApiException $e) {
    // Handle other API errors
    $errorDetails = $e->getErrorDetails();
}
```

## Best Practices

1. **Performance Optimization**

   - Use appropriate limits
   - Implement caching for repeated searches
   - Use alternative dates search for flexible dates

2. **Error Handling**

   - Always handle rate limits
   - Log search errors
   - Implement retry logic

3. **Search Parameters**

   - Validate dates
   - Check airport codes
   - Verify passenger types

4. **Response Processing**
   - Cache search results
   - Filter irrelevant options
   - Sort by relevance

## Example Implementations

### Basic Flight Search

```php
use Santosdave\SabreWrapper\Facades\Sabre;
use Santosdave\SabreWrapper\Models\Air\BargainFinderMaxRequest;

class FlightSearchService
{
    public function searchFlights(array $params)
    {
        $request = new BargainFinderMaxRequest();

        // Add search criteria
        $request->addOriginDestination(
            $params['origin'],
            $params['destination'],
            $params['departureDate']
        );

        // Add passengers
        foreach ($params['passengers'] as $type => $count) {
            $request->addTraveler($type, $count);
        }

        // Add preferences
        if (!empty($params['airlines'])) {
            $request->setTravelPreferences([
                'vendorPrefs' => $params['airlines']
            ]);
        }

        // Execute search
        try {
            $response = Sabre::shopping()
                ->bargainFinderMax($request);

            return [
                'success' => true,
                'data' => $response->getOffers(),
                'summary' => $response->getSummary()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
```

### Advanced Search with Caching

```php
use Illuminate\Support\Facades\Cache;

class CachedFlightSearch
{
    private function getCacheKey(array $params): string
    {
        return 'flight_search_' . md5(serialize($params));
    }

    public function search(array $params)
    {
        $cacheKey = $this->getCacheKey($params);

        return Cache::remember($cacheKey, 300, function () use ($params) {
            $request = new BargainFinderMaxRequest();

            // Configure request
            $request->addOriginDestination(
                $params['origin'],
                $params['destination'],
                $params['departureDate']
            );

            // Execute search with retries
            return retry(3, function () use ($request) {
                return Sabre::shopping()
                    ->bargainFinderMax($request);
            }, 100);
        });
    }
}
```

## Rate Limits

The shopping service has the following rate limits:

- bargainFinderMax: 50 requests per minute
- alternativeDatesSearch: 30 requests per minute
- instaFlights: 40 requests per minute

## Webhooks

Available webhook events:

- `shopping.search.completed`
- `shopping.cache.invalidated`
- `shopping.error.occurred`

## Monitoring

Monitor shopping service health:

```php
$metrics = Sabre::health()->getServiceMetrics('shopping');
```
