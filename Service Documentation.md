# Service Documentation

## Shopping Service

### Overview

The Shopping Service provides advanced air shopping capabilities through Sabre's APIs, supporting both REST and SOAP endpoints. It implements rate limiting, caching, and retry strategies.

### Features

- Bargain Finder Max (BFM) search
- Alternative dates search
- InstaFlights search
- Support for NDC content
- Configurable rate limiting
- Response caching
- Automatic retry on failures

### Implementation

```php
interface AirShoppingServiceInterface
{
    public function bargainFinderMax(BargainFinderMaxRequest $request): BargainFinderMaxResponse;
    public function alternativeDatesSearch(BargainFinderMaxRequest $request): BargainFinderMaxResponse;
    public function instaFlights(
        string $origin,
        string $destination,
        string $departureDate,
        ?string $returnDate = null,
        int $limit = 50
    ): array;
}

class ShoppingService extends BaseRestService implements AirShoppingServiceInterface
{
    // Implementation
}
```

### Usage Examples

```php
// Basic search
$request = new BargainFinderMaxRequest($pseudoCityCode);
$request->addOriginDestination(
    'JFK',
    'LHR',
    '2024-03-15'
)
->addTraveler('ADT', 1)
->setTravelPreferences([
    'vendorPrefs' => ['AA', 'BA'],
    'cabinPrefs' => ['Y']
]);

$results = Sabre::shopping()->bargainFinderMax($request);

// Alternative dates search
$request = new BargainFinderMaxRequest($pseudoCityCode);
$request->addOriginDestination(...)
    ->setDateFlexibility(3); // +/- 3 days

$results = Sabre::shopping()->alternativeDatesSearch($request);

// InstaFlights search
$results = Sabre::shopping()->instaFlights(
    'JFK',
    'LHR',
    '2024-03-15',
    null,
    50
);
```

### Rate Limits

```php
'limits' => [
    'shopping' => [
        'bargain_finder_max' => ['limit' => 50, 'window' => 60],
        'alternative_dates' => ['limit' => 30, 'window' => 60],
        'insta_flights' => ['limit' => 40, 'window' => 60]
    ]
]
```

### Error Handling

```php
try {
    $results = $service->bargainFinderMax($request);
} catch (SabreRateLimitException $e) {
    Log::warning('Rate limit exceeded', [
        'retry_after' => $e->getRetryAfter(),
        'reset' => $e->getReset()
    ]);
} catch (SabreApiException $e) {
    Log::error('Shopping failed', [
        'error' => $e->getMessage(),
        'details' => $e->getErrorDetails()
    ]);
}
```

## Order Management Service

### Overview

The Order Management Service handles NDC order creation, modification, and fulfillment.

### Features

- Order creation and validation
- Order modification
- Order fulfillment
- Order splitting
- Order cancellation
- Exchange processing

### Implementation

```php
interface OrderManagementServiceInterface
{
    public function createOrder(OrderCreateRequest $request): OrderCreateResponse;
    public function viewOrder(OrderViewRequest $request): OrderViewResponse;
    public function changeOrder(OrderChangeRequest $request): OrderChangeResponse;
    public function fulfillOrder(OrderFulfillRequest $request): OrderFulfillResponse;
    public function splitOrder(OrderSplitRequest $request): OrderSplitResponse;
    // ... other methods
}

class OrderManagementService extends BaseRestService implements OrderManagementServiceInterface
{
    // Implementation
}
```

### Usage Examples

```php
// Create order
$request = new OrderCreateRequest();
$request->setOffer($offerId, [$offerItemId])
    ->addPassenger(
        'PAX1',
        'John',
        'Doe',
        '1990-01-01',
        'ADT',
        'CI-1'
    );

$order = Sabre::order()->createOrder($request);

// Fulfill order
$fulfillRequest = new OrderFulfillRequest($order->getOrderId());
$fulfillRequest->setPaymentCard(
    $cardNumber,
    $expirationDate,
    $vendorCode,
    $cvv,
    'CI-1'
);

$result = Sabre::order()->fulfillOrder($fulfillRequest);
```

## Ancillary Service

### Overview

The Ancillary Service manages additional services like baggage, seats, and meals.

### Features

- Get available ancillaries
- Add ancillaries to orders
- Price ancillary services
- Remove ancillaries
- Get ancillary rules

### Implementation

```php
interface AncillaryServiceInterface
{
    public function getAncillaries(AncillaryRequest $request): AncillaryResponse;
    public function getPrebookableAncillaries(string $orderId): AncillaryResponse;
    public function getPostbookableAncillaries(
        string $orderId,
        ?array $segments = null
    ): AncillaryResponse;
    // ... other methods
}

class AncillaryService extends BaseRestService implements AncillaryServiceInterface
{
    // Implementation
}
```

### Usage Examples

```php
// Get available ancillaries
$request = new AncillaryRequest();
$request->setTravelAgencyParty($pseudoCityId, $agencyId)
    ->addFlightSegment(
        'SEG1',
        'JFK',
        'LHR',
        '2024-03-15',
        'AA',
        '100',
        'Y'
    );

$ancillaries = Sabre::ancillary()->getAncillaries($request);

// Add ancillary to order
$result = Sabre::ancillary()->addAncillaryToOrder(
    $orderId,
    $serviceId,
    $passengers,
    $paymentInfo
);
```

## Seat Service

### Overview

The Seat Service handles seat map retrieval and seat assignments.

### Features

- Get seat maps
- Assign seats
- Remove seat assignments
- Get seat rules
- Validate seat assignments

### Implementation

```php
interface SeatServiceInterface
{
    public function getSeatMap(SeatMapRequest $request): SeatMapResponse;
    public function assignSeats(SeatAssignRequest $request): SeatAssignResponse;
    public function removeSeatAssignment(
        string $orderId,
        string $passengerId,
        string $segmentId
    ): SeatAssignResponse;
    // ... other methods
}

class SeatService extends BaseRestService implements SeatServiceInterface
{
    // Implementation
}
```

### Usage Examples

```php
// Get seat map
$request = new SeatMapRequest();
$seatMap = Sabre::seat()->getSeatMap($request);

// Assign seats
$seatRequest = new SeatAssignRequest($orderId);
$seatRequest->addSeatAssignment(
    'PAX1',
    'SEG1',
    '12A',
    ['window' => true]
);

$result = Sabre::seat()->assignSeats($seatRequest);
```
