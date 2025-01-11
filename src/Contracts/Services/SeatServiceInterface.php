<?php

namespace Santosdave\SabreWrapper\Contracts\Services;

use Santosdave\SabreWrapper\Models\Air\Seat\SeatMapRequest;
use Santosdave\SabreWrapper\Models\Air\Seat\SeatMapResponse;
use Santosdave\SabreWrapper\Models\Air\Seat\SeatAssignRequest;
use Santosdave\SabreWrapper\Models\Air\Seat\SeatAssignResponse;

interface SeatServiceInterface
{
    /**
     * Get seat map for a flight
     */
    public function getSeatMap(SeatMapRequest $request): SeatMapResponse;

    /**
     * Get seat map for an existing order
     */
    public function getSeatMapForOrder(string $orderId): SeatMapResponse;

    /**
     * Assign seats for passengers
     */
    public function assignSeats(SeatAssignRequest $request): SeatAssignResponse;

    /**
     * Remove seat assignment
     */
    public function removeSeatAssignment(
        string $orderId,
        string $passengerId,
        string $segmentId
    ): SeatAssignResponse;

    /**
     * Get seat assignment rules for a carrier
     */
    public function getSeatRules(
        string $carrierCode,
        ?array $seatTypes = null
    ): array;

    /**
     * Validate seat assignments before processing
     */
    public function validateSeatAssignment(
        string $orderId,
        array $assignments
    ): bool;
}
