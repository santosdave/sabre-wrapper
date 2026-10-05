<?php

namespace Santosdave\SabreWrapper\Tests;

use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Http\Rest\RestClient;
use Santosdave\SabreWrapper\Services\Auth\SabreAuthenticator;

/**
 * A failed REST call is only sent again when that cannot do anything twice. Sabre's reads
 * are mostly POSTs (shop, price, getBooking), so they are named; anything else (create or
 * cancel a booking, issue, exchange) may already have been applied when it failed, and
 * sending it again could book twice: it is reported instead.
 */
class RetryPolicyTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('sabre.request.retries', 3);
    }

    private static function client(): RestClient
    {
        return new RestClient(app(SabreAuthenticator::class), 'cert');
    }

    private function calls(string $path): int
    {
        return count(array_filter($this->sabre->requests, fn ($r) => $r->getUri()->getPath() === $path));
    }

    public function test_a_booking_that_failed_is_never_sent_again(): void
    {
        $this->sabre->statuses['/v1/trip/orders/createBooking'] = [503, 200];

        try {
            self::client()->post('/v1/trip/orders/createBooking', ['flightOffer' => []]);
            $this->fail('Expected the failure to surface');
        } catch (SabreApiException $e) {
            $this->assertSame(503, $e->getCode());
        }
        $this->assertSame(1, $this->calls('/v1/trip/orders/createBooking'));
    }

    public function test_a_cancellation_that_failed_is_never_sent_again(): void
    {
        $this->sabre->statuses['/v1/trip/orders/cancelBooking'] = [500, 200];

        try {
            self::client()->post('/v1/trip/orders/cancelBooking', ['confirmationId' => 'ABC123']);
            $this->fail('Expected the failure to surface');
        } catch (SabreApiException) {
        }
        $this->assertSame(1, $this->calls('/v1/trip/orders/cancelBooking'));
    }

    public function test_a_retrieve_that_failed_is_tried_again(): void
    {
        $this->sabre->statuses['/v1/trip/orders/getBooking'] = [503, 200];

        $result = self::client()->post('/v1/trip/orders/getBooking', ['confirmationId' => 'ABC123']);

        $this->assertSame('ABC123', $result['confirmationId']);
        $this->assertSame(2, $this->calls('/v1/trip/orders/getBooking'));
    }

    public function test_a_shop_that_failed_is_tried_again(): void
    {
        $this->sabre->statuses['/v3/offers/shop'] = [502, 200];

        self::client()->post('/v3/offers/shop', ['OTA_AirLowFareSearchRQ' => []]);

        $this->assertSame(2, $this->calls('/v3/offers/shop'));
    }
}
