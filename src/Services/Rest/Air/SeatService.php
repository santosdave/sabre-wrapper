<?php

namespace Santosdave\SabreWrapper\Services\Rest\Air;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\SeatServiceInterface;

use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Models\Air\Seat\SeatAssignRequest;
use Santosdave\SabreWrapper\Models\Air\Seat\SeatAssignResponse;
use Santosdave\SabreWrapper\Models\Air\Seat\SeatMapRequest;
use Santosdave\SabreWrapper\Models\Air\Seat\SeatMapResponse;

class SeatService extends BaseRestService implements SeatServiceInterface
{
    public function getSeatMap(SeatMapRequest $request): SeatMapResponse
    {
        try {
            $response = $this->client->post(
                '/v1/offers/getseats',
                $request->toArray()
            );
            return new SeatMapResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get seat map: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getSeatMapForOrder(string $orderId): SeatMapResponse
    {
        try {
            $response = $this->client->get("/v1/orders/{$orderId}/seats");
            return new SeatMapResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get order seat map: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function assignSeats(SeatAssignRequest $request): SeatAssignResponse
    {
        try {
            $response = $this->client->post(
                "/v1/orders/{$request->toArray()['orderId']}/seats",
                $request->toArray()
            );
            return new SeatAssignResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to assign seats: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function removeSeatAssignment(
        string $orderId,
        string $passengerId,
        string $segmentId
    ): SeatAssignResponse {
        try {
            $response = $this->client->post(
                "/v1/orders/{$orderId}/seats",
                [
                    'passengerId' => $passengerId,
                    'segmentId' => $segmentId
                ]
            );
            return new SeatAssignResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to remove seat assignment: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getSeatRules(
        string $carrierCode,
        ?array $seatTypes = null
    ): array {
        try {
            $params = ['carrier' => $carrierCode];
            if ($seatTypes) {
                $params['seatTypes'] = implode(',', $seatTypes);
            }

            return $this->client->get('/v1/offers/seats/rules', $params);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get seat rules: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function validateSeatAssignment(
        string $orderId,
        array $assignments
    ): bool {
        try {
            $response = $this->client->post(
                "/v1/orders/{$orderId}/seats/validate",
                ['assignments' => $assignments]
            );
            return $response['valid'] ?? false;
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to validate seat assignments: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
