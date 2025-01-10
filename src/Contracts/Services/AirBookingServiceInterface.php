<?php

namespace Santosdave\Sabre\Contracts\Services;

use Santosdave\Sabre\Models\Air\Booking\CreateBookingRequest;
use Santosdave\Sabre\Models\Air\Booking\CreateBookingResponse;
use Santosdave\Sabre\Models\Air\CreatePnrRequest;
use Santosdave\Sabre\Models\Air\CreatePnrResponse;
use Santosdave\Sabre\Models\Air\EnhancedAirBookRequest;
use Santosdave\Sabre\Models\Air\Order\OrderCancelResponse;
use Santosdave\Sabre\Models\Air\PassengerDetailsRequest;

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