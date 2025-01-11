# Sabre API Wrapper Documentation

## Overview

The Sabre API Wrapper provides a comprehensive interface for interacting with Sabre's NDC (New Distribution Capability) APIs. This wrapper simplifies the integration process and handles common tasks like authentication, rate limiting, and error handling.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Authentication](#authentication)
4. [Basic Usage](#basic-usage)
5. [NDC Flows](#ndc-flows)
6. [Error Handling](#error-handling)
7. [Rate Limiting](#rate-limiting)
8. [Health Monitoring](#health-monitoring)
9. [Webhooks](#webhooks)
10. [Best Practices](#best-practices)

## Installation

```bash
composer require santosdave/sabre-wrapper
```

After installation, publish the configuration file:

```bash
php artisan vendor:publish --provider="Santosdave\SabreWrapper\SabreServiceProvider"
```

## Configuration

Configure your Sabre credentials in your `.env` file:

```env
SABRE_USERNAME=your_username
SABRE_PASSWORD=your_password
SABRE_PCC=your_pcc
SABRE_CLIENT_ID=your_client_id
SABRE_CLIENT_SECRET=your_client_secret
SABRE_ENVIRONMENT=cert # or prod
```

## Authentication

The wrapper handles authentication automatically, but you can also manage it manually:

```php
use Santosdave\SabreWrapper\Facades\Sabre;

// Get authenticated client
$client = Sabre::client();

// Force token refresh
$client->refreshToken();

// Check token status
$isValid = $client->hasValidToken();
```

## Basic Usage

### Air Shopping

```php
use Santosdave\SabreWrapper\Facades\Sabre;

// Search for flights
$results = Sabre::shopping()->bargainFinderMax([
    'origin' => 'JFK',
    'destination' => 'LHR',
    'departureDate' => '2024-03-15',
    'returnDate' => '2024-03-22',
    'passengers' => [
        ['type' => 'ADT', 'count' => 1]
    ]
]);

// Get price quotes
$priceQuote = Sabre::pricing()->priceItinerary($results->getSelectedItinerary());
```

### Booking Management

```php
// Create a booking
$booking = Sabre::booking()->createPnr([
    'itinerary' => $itinerary,
    'passengers' => [
        [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'type' => 'ADT'
        ]
    ],
    'contact' => [
        'email' => 'john@example.com',
        'phone' => '+1234567890'
    ]
]);

// Retrieve booking details
$details = Sabre::booking()->getBooking($booking->getLocator());

// Cancel booking
$cancelled = Sabre::booking()->cancelBooking($booking->getLocator());
```

### Advanced NDC Operations

```php
// Create order
$order = Sabre::order()->createOrder([
    'offerId' => $offerId,
    'passengers' => $passengers,
    'payments' => $payments
]);

// Fulfill order
$fulfilled = Sabre::order()->fulfillOrder($order->getId(), [
    'payment' => [
        'method' => 'creditCard',
        'card' => $cardDetails
    ]
]);
```

## NDC Flows

### Basic NDC Flow

1. Shopping
```php
$request = new BargainFinderMaxRequest();
$request->addOriginDestination('JFK', 'LHR', '2024-03-15');
$results = Sabre::shopping()->bargainFinderMax($request);
```

2. Price Verification
```php
$priceRequest = new OfferPriceRequest($results->getSelectedOffer());
$priceResponse = Sabre::pricing()->priceOffer($priceRequest);
```

3. Booking Creation
```php
$bookingRequest = new CreateBookingRequest();
$bookingRequest->setOffer($priceResponse->getOfferId())
    ->addPassenger($passengerDetails)
    ->setContactInfo($contactInfo);

$booking = Sabre::booking()->createBooking($bookingRequest);
```

### Advanced NDC Flow

1. Order Creation
```php
$orderRequest = new OrderCreateRequest();
$orderRequest->setOffer($offerId)
    ->addPassenger($passengerDetails)
    ->setPaymentInfo($paymentDetails);

$order = Sabre::order()->createOrder($orderRequest);
```

2. Order Fulfillment
```php
$fulfillRequest = new OrderFulfillRequest($order->getId());
$fulfillRequest->setPaymentCard($cardDetails);

$fulfilled = Sabre::order()->fulfillOrder($fulfillRequest);
```

## Error Handling

The wrapper provides comprehensive error handling:

```php
use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Exceptions\SabreAuthenticationException;
use Santosdave\SabreWrapper\Exceptions\SabreRateLimitException;

try {
    $results = Sabre::shopping()->bargainFinderMax($request);
} catch (SabreRateLimitException $e) {
    // Handle rate limiting
    $retryAfter = $e->getRetryAfter();
} catch (SabreAuthenticationException $e) {
    // Handle authentication errors
} catch (SabreApiException $e) {
    // Handle other API errors
    $errorDetails = $e->getErrorDetails();
}
```

## Rate Limiting

The wrapper includes built-in rate limiting:

```php
// Configure rate limits
config(['sabre.rate_limiting.enabled' => true]);
config(['sabre.rate_limiting.default_limit' => 100]);

// Check current rate limit status
$status = Sabre::getRateLimitStatus();

// Handle rate limit exceeded
try {
    $results = Sabre::shopping()->bargainFinderMax($request);
} catch (SabreRateLimitException $e) {
    $retryAfter = $e->getRetryAfter();
    $resetTime = $e->getReset();
}
```

## Health Monitoring

Monitor the health of your Sabre integration:

```php
// Get current health status
$health = Sabre::health()->checkStatus();

// Get detailed metrics
$metrics = Sabre::health()->getMetrics();

// Monitor specific services
$bookingHealth = Sabre::health()->checkService('booking');
```

## Webhooks

Handle Sabre webhook events:

```php
use Santosdave\SabreWrapper\Events\Webhook\OrderStatusChanged;
use Santosdave\SabreWrapper\Events\Webhook\BookingCreated;

// Register webhook handlers
Event::listen(OrderStatusChanged::class, function ($event) {
    $orderId = $event->getOrderId();
    $newStatus = $event->getNewStatus();
    // Handle order status change
});

Event::listen(BookingCreated::class, function ($event) {
    $booking = $event->getBooking();
    // Handle new booking
});
```

## Best Practices

1. **Error Handling**
   - Always wrap API calls in try-catch blocks
   - Handle rate limiting appropriately
   - Log all API errors for debugging

2. **Rate Limiting**
   - Implement exponential backoff
   - Use queue system for high-volume operations
   - Monitor rate limit status

3. **Performance**
   - Enable response caching where appropriate
   - Use batch operations when possible
   - Implement proper connection pooling

4. **Security**
   - Store credentials securely
   - Implement proper access control
   - Monitor for suspicious activity

5. **Monitoring**
   - Set up health checks
   - Monitor API response times
   - Track error rates