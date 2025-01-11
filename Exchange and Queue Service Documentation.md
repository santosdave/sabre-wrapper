# Exchange and Queue Service Documentation

## Exchange Service

### Overview

The Exchange Service handles ticket exchanges, refunds, and reissues for existing bookings.

### Features

- Exchange search and validation
- Exchange booking
- Refund quotes
- Exchange pricing
- Exchange fulfillment

### Implementation

```php
interface ExchangeServiceInterface
{
    public function searchExchanges(ExchangeSearchRequest $request): ExchangeSearchResponse;
    public function bookExchange(ExchangeBookRequest $request): ExchangeBookResponse;
    public function getRefundQuote(RefundQuoteRequest $request): RefundQuoteResponse;
    public function validateExchange(string $pnr): ExchangeSearchResponse;
}

class ExchangeService extends BaseRestService implements ExchangeServiceInterface
{
    public function searchExchanges(ExchangeSearchRequest $request): ExchangeSearchResponse
    {
        try {
            $response = $this->client->post(
                '/v1/air/exchange/search',
                $request->toArray()
            );
            return new ExchangeSearchResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to search exchanges: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
    // Other method implementations...
}
```

### Usage Examples

```php
// Search for exchange options
$request = new ExchangeSearchRequest($pnr);
$request->addNewSegment(
    'JFK',
    'LHR',
    '2024-03-20',
    'AA',
    '100',
    'Y'
);

$options = Sabre::exchange()->searchExchanges($request);

// Book exchange
$bookRequest = new ExchangeBookRequest();
$bookRequest->setBookingDetails([
    'newSegments' => $options->getSelectedSegments(),
    'passengers' => $existingPassengers,
    'payment' => $paymentDetails
]);

$result = Sabre::exchange()->bookExchange($bookRequest);

// Get refund quote
$quoteRequest = new RefundQuoteRequest(
    $ticketNumber,
    $passengerName,
    $refundAmount
);

$quote = Sabre::exchange()->getRefundQuote($quoteRequest);
```

### Error Handling

```php
try {
    $result = $service->bookExchange($request);
} catch (SabreApiException $e) {
    if ($e->getCode() === 'EXCHANGE_NOT_ALLOWED') {
        // Handle exchange restriction
    } elseif ($e->getCode() === 'INVALID_FARE_CALCULATION') {
        // Handle pricing issues
    } else {
        // Handle other errors
    }
}
```

## Queue Service

### Overview

The Queue Service manages Sabre queues for bookings, ticketing, and other operations.

### Features

- Queue listing and access
- Queue placement
- Queue removal
- Queue movement
- PNR retrieval from queue

### Implementation

```php
interface QueueServiceInterface
{
    public function listQueue(QueueListRequest $request): QueueListResponse;
    public function placeOnQueue(QueuePlaceRequest $request): bool;
    public function removeFromQueue(QueueRemoveRequest $request): bool;
    public function getPnrFromQueue(string $queueNumber, int $recordLocator): array;
    public function moveQueue(
        string $sourceQueue,
        string $targetQueue,
        ?array $criteria = null
    ): bool;
}

class QueueService extends BaseRestService implements QueueServiceInterface
{
    public function listQueue(QueueListRequest $request): QueueListResponse
    {
        try {
            $response = $this->client->post(
                '/v1/queue/list',
                $request->toArray()
            );
            return new QueueListResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to list queue: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
    // Other method implementations...
}
```

### Usage Examples

```php
// List queue contents
$request = new QueueListRequest($queueNumber);
$response = Sabre::queue()->listQueue($request);

// Process queue items
foreach ($response->getEntries() as $entry) {
    try {
        // Handle queue entry
        $pnr = $entry['pnr'];
        $passengers = $entry['passengers'];

        // Process the entry
        $result = processQueueEntry($pnr, $passengers);

        // Remove from queue if successful
        if ($result) {
            $removeRequest = new QueueRemoveRequest($queueNumber);
            Sabre::queue()->removeFromQueue($removeRequest);
        }
    } catch (SabreApiException $e) {
        Log::error('Queue processing failed', [
            'pnr' => $pnr,
            'error' => $e->getMessage()
        ]);
    }
}

// Move queue items
$moved = Sabre::queue()->moveQueue(
    '50',  // Source queue
    '100', // Target queue
    [
        'category' => 'TICKETING',
        'dateRange' => [
            'from' => '2024-03-15',
            'to' => '2024-03-16'
        ]
    ]
);
```

### Queue Configuration

```php
// config/sabre.php
return [
    'queues' => [
        'default_queue' => env('SABRE_DEFAULT_QUEUE', '100'),
        'default_category' => env('SABRE_DEFAULT_QUEUE_CATEGORY', '0'),
        'auto_remove' => env('SABRE_QUEUE_AUTO_REMOVE', true),
        'polling' => [
            'enabled' => env('SABRE_QUEUE_POLLING_ENABLED', false),
            'interval' => env('SABRE_QUEUE_POLLING_INTERVAL', 300), // 5 minutes
            'max_items' => env('SABRE_QUEUE_POLLING_MAX_ITEMS', 50)
        ],
        'retry' => [
            'attempts' => env('SABRE_QUEUE_RETRY_ATTEMPTS', 3),
            'delay' => env('SABRE_QUEUE_RETRY_DELAY', 5)
        ]
    ]
];
```

### Queue Categories

Common queue categories and their uses:

1. General Queues (0-9)

   - Queue 0: Default queue
   - Queue 1: Schedule changes
   - Queue 2: Waitlist clearance

2. Ticketing Queues (10-19)

   - Queue 10: Tickets requiring attention
   - Queue 11: Exchange processing
   - Queue 12: Refunds

3. Special Handling Queues (20-29)
   - Queue 20: VIP bookings
   - Queue 21: Group bookings
   - Queue 22: Corporate bookings

### Best Practices

1. Queue Management

   ```php
   public function processQueue(string $queueNumber)
   {
       $processed = 0;
       $maxItems = config('sabre.queues.polling.max_items');

       while ($processed < $maxItems) {
           $entries = Sabre::queue()->listQueue(new QueueListRequest($queueNumber));

           if (empty($entries->getEntries())) {
               break;
           }

           foreach ($entries->getEntries() as $entry) {
               try {
                   // Process with retry mechanism
                   $this->retryService->execute(function () use ($entry) {
                       return $this->processEntry($entry);
                   });

                   $processed++;

                   // Remove from queue if successful
                   $removeRequest = new QueueRemoveRequest($queueNumber);
                   $removeRequest->addEntry($entry['id']);
                   Sabre::queue()->removeFromQueue($removeRequest);

               } catch (\Exception $e) {
                   Log::error('Queue entry processing failed', [
                       'queue' => $queueNumber,
                       'entry' => $entry['id'],
                       'error' => $e->getMessage()
                   ]);

                   // Move to error queue if configured
                   if (config('sabre.queues.error_handling.move_to_error_queue')) {
                       $this->moveToErrorQueue($entry, $e);
                   }
               }
           }

           // Check if we should continue polling
           if (!config('sabre.queues.polling.enabled')) {
               break;
           }

           // Sleep between batches
           sleep(config('sabre.queues.polling.delay', 5));
       }
   }

   private function moveToErrorQueue(array $entry, \Exception $error): void
   {
       $errorQueue = config('sabre.queues.error_handling.error_queue', '99');

       $request = new QueuePlaceRequest();
       $request->setPnr($entry['pnr'])
           ->setQueue($errorQueue)
           ->addRemark('Error: ' . $error->getMessage());

       Sabre::queue()->placeOnQueue($request);
   }
   }
   ```

````

2. Queue Monitoring

```php
class QueueMonitoringService
{
    public function getQueueMetrics(): array
    {
        $metrics = [];

        foreach ($this->getMonitoredQueues() as $queue) {
            $request = new QueueListRequest($queue);
            $response = Sabre::queue()->listQueue($request);

            $metrics[$queue] = [
                'count' => count($response->getEntries()),
                'oldest_entry' => $this->getOldestEntry($response),
                'processing_rate' => $this->getProcessingRate($queue),
                'error_rate' => $this->getErrorRate($queue)
            ];
        }

        return $metrics;
    }

    private function getProcessingRate(string $queue): float
    {
        $key = "queue_processing_rate_{$queue}_" . now()->format('Y-m-d-H');
        return Cache::get($key, 0.0);
    }
}
````

3. Automated Queue Processing

```php
class QueueProcessingJob implements ShouldQueue
{
    public function handle()
    {
        $queues = config('sabre.queues.automated_processing', []);

        foreach ($queues as $queue => $config) {
            if ($this->shouldProcess($queue, $config)) {
                dispatch(new ProcessQueueJob($queue, $config));
            }
        }
    }

    private function shouldProcess(string $queue, array $config): bool
    {
        // Check scheduling
        if (!$this->isWithinProcessingHours($config['schedule'])) {
            return false;
        }

        // Check queue size
        $request = new QueueListRequest($queue);
        $response = Sabre::queue()->listQueue($request);

        return count($response->getEntries()) >= $config['min_entries'];
    }
}
```

## Pricing Service

### Overview

The Pricing Service handles fare pricing, pricing validation, and revalidation.

### Implementation

```php
interface AirPricingServiceInterface
{
    public function priceItinerary(PriceItineraryRequest $request): PriceItineraryResponse;
    public function validatePrice(ValidatePriceRequest $request): ValidatePriceResponse;
    public function revalidateItinerary(string $pnr): PriceItineraryResponse;
    public function getPriceQuote(string $pnr): PriceItineraryResponse;
    public function priceOffer(OfferPriceRequest $request): OfferPriceResponse;
}

class PricingService extends BaseRestService implements AirPricingServiceInterface
{
    public function priceItinerary(PriceItineraryRequest $request): PriceItineraryResponse
    {
        try {
            $response = $this->client->post(
                '/v2/air/price',
                $request->toArray()
            );
            return new PriceItineraryResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to price itinerary: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    // Other implementations...
}
```

### Usage Examples

```php
// Price an itinerary
$request = new PriceItineraryRequest();
$request->addSegment(
    'JFK',
    'LHR',
    'AA',
    '100',
    '2024-03-15T10:00:00',
    'Y'
);

$request->addPassenger('ADT', 1);
$response = Sabre::pricing()->priceItinerary($request);

// Validate pricing
$validateRequest = new ValidatePriceRequest();
$validated = Sabre::pricing()->validatePrice($validateRequest);

// Price NDC offer
$offerRequest = new OfferPriceRequest();
$offerRequest->addOfferItem($offerId)
    ->setCreditCard('MC', '545251', 'FDA');

$priced = Sabre::pricing()->priceOffer($offerRequest);
```

### Error Handling

```php
try {
    $response = $service->priceItinerary($request);
} catch (SabreApiException $e) {
    if ($e->getCode() === 'FARE_NOT_AVAILABLE') {
        // Handle fare no longer available
    } elseif ($e->getCode() === 'INVALID_FARE_BASIS') {
        // Handle invalid fare basis
    } else {
        // Handle other pricing errors
    }
}
```
