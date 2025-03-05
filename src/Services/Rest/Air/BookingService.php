<?php

namespace Santosdave\SabreWrapper\Services\Rest\Air;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\AirBookingServiceInterface;
use Santosdave\SabreWrapper\Models\Air\CreatePnrRequest;
use Santosdave\SabreWrapper\Models\Air\CreatePnrResponse;
use Santosdave\SabreWrapper\Models\Air\EnhancedAirBookRequest;
use Santosdave\SabreWrapper\Models\Air\PassengerDetailsRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Models\Air\Booking\CreateBookingRequest;
use Santosdave\SabreWrapper\Models\Air\Booking\CreateBookingResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderCancelResponse;

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
            // Determine the appropriate endpoint based on booking type
            $endpoint = $this->determineBookingEndpoint($request);

            // Prepare the request payload
            $payload = $this->prepareBookingPayload($request);

            // Make the API call
            $response = $this->client->post($endpoint, $payload);

            // Create and return the booking response
            return new CreateBookingResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to create booking: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    private function determineBookingEndpoint(CreateBookingRequest $request): string
    {
        // Check if it's an NDC booking (has flightOffer)
        if ($request->hasNdcOffer()) {
            return '/v1/trip/orders/createBooking';
        }

        // For non-NDC bookings, use traditional endpoint
        return '/v1/trip/orders/createBooking';
    }

    private function prepareBookingPayload(CreateBookingRequest $request): array
    {
        $payload = $request->toArray();

        // Additional preprocessing for specific booking types
        if ($request->hasNdcOffer()) {
            // NDC-specific payload modifications
            $payload = $this->preprocessNdcBooking($payload);
        } else {
            // Non-NDC specific payload modifications
            $payload = $this->preprocessTraditionalBooking($payload);
        }

        return $payload;
    }

    private function preprocessNdcBooking(array $payload): array
    {
        // Add default values for NDC booking if not present
        $payload['asynchronousUpdateWaitTime'] = $payload['asynchronousUpdateWaitTime'] ?? 3000;

        // Ensure consistent payload structure
        if (isset($payload['flightOffer'])) {
            $payload['flightOffer'] = [
                'offerId' => $payload['flightOffer']['offerId'] ?? null,
                'selectedOfferItems' => $payload['flightOffer']['selectedOfferItems'] ?? []
            ];
        }

        return $payload;
    }

    private function preprocessTraditionalBooking(array $payload): array
    {
        // Add default agency information if not present
        if (!isset($payload['agency'])) {
            $payload['agency'] = [
                'address' => [
                    'countryCode' => 'KE'
                ]
            ];
        }

        // Ensure flight details are properly structured
        if (isset($payload['flightDetails']['flights'])) {
            $payload['flightDetails']['flights'] = array_map(function ($flight) {
                // Set default values for missing fields
                $flight['flightStatusCode'] = $flight['flightStatusCode'] ?? 'NN';
                $flight['marriageGroup'] = $flight['marriageGroup'] ?? false;
                return $flight;
            }, $payload['flightDetails']['flights']);
        }

        return $payload;
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
