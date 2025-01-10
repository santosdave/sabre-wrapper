<?php

namespace Santosdave\Sabre\Services\Rest\Air;

use Santosdave\Sabre\Services\Base\BaseRestService;
use Santosdave\Sabre\Contracts\Services\AirBookingServiceInterface;
use Santosdave\Sabre\Models\Air\CreatePnrRequest;
use Santosdave\Sabre\Models\Air\CreatePnrResponse;
use Santosdave\Sabre\Models\Air\EnhancedAirBookRequest;
use Santosdave\Sabre\Models\Air\PassengerDetailsRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;
use Santosdave\Sabre\Models\Air\Booking\CreateBookingRequest;
use Santosdave\Sabre\Models\Air\Booking\CreateBookingResponse;
use Santosdave\Sabre\Models\Air\Order\OrderCancelResponse;

class BookingService extends BaseRestService implements AirBookingServiceInterface
{
    public function createPnr(CreatePnrRequest $request): CreatePnrResponse
    {
        try {
            $response = $this->client->post(
                '/v2.4.0/passenger/records',
                $request->toArray()
            );
            return new CreatePnrResponse($response, 'rest');
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to create PNR: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function enhancedAirBook(EnhancedAirBookRequest $request): array
    {
        try {
            return $this->client->post(
                '/v3.10.0/book/flights',
                $request->toArray()
            );
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to perform enhanced air book: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function addPassengerDetails(PassengerDetailsRequest $request): array
    {
        try {
            return $this->client->post(
                '/v3.4.0/passenger/records/details',
                $request->toArray()
            );
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to add passenger details: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function cancelPnr(string $pnr): bool
    {
        try {
            $response = $this->client->post("/v1/pnr/{$pnr}");
            return isset($response['status']) && $response['status'] === 'success';
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to cancel PNR: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function createBooking(CreateBookingRequest $request): CreateBookingResponse
    {
        try {
            $response = $this->client->post(
                '/v1/trip/orders/createBooking',
                $request->toArray()
            );
            return new CreateBookingResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to create booking: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getBooking(string $confirmationId): CreateBookingResponse
    {
        try {
            $response = $this->client->post('/v1/trip/orders/getBooking', [
                'confirmationId' => $confirmationId
            ]);
            return new CreateBookingResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get booking: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function cancelBooking(
        string $confirmationId,
        bool $retrieveBooking = true,
        bool $cancelAll = true
    ): OrderCancelResponse {
        try {
            $request = [
                'confirmationId' => $confirmationId,
                'retrieveBooking' => $retrieveBooking,
                'cancelAll' => $cancelAll
            ];

            $response = $this->client->post(
                '/v1/trip/orders/cancelBooking',
                $request
            );
            return new OrderCancelResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to cancel booking: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}