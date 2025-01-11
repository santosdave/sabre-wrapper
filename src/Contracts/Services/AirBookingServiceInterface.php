<?php

namespace Santosdave\SabreWrapper\Contracts\Services;

use Santosdave\SabreWrapper\Models\Air\Booking\CreateBookingRequest;
use Santosdave\SabreWrapper\Models\Air\Booking\CreateBookingResponse;
use Santosdave\SabreWrapper\Models\Air\CreatePnrRequest;
use Santosdave\SabreWrapper\Models\Air\CreatePnrResponse;
use Santosdave\SabreWrapper\Models\Air\EnhancedAirBookRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderCancelResponse;
use Santosdave\SabreWrapper\Models\Air\PassengerDetailsRequest;

interface AirBookingServiceInterface
{
    public function createPnr(CreatePnrRequest $request): CreatePnrResponse;
    public function enhancedAirBook(EnhancedAirBookRequest $request): array;
    public function addPassengerDetails(PassengerDetailsRequest $request): array;
    public function cancelPnr(string $pnr): bool;


    // NDC Basic Flow Methods
    public function createBooking(CreateBookingRequest $request): CreateBookingResponse;
    public function getBooking(string $confirmationId): CreateBookingResponse;
    public function cancelBooking(
        string $confirmationId,
        bool $retrieveBooking = true,
        bool $cancelAll = true
    ): OrderCancelResponse;
}
