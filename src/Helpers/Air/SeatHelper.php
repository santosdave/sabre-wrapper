<?php

namespace Santosdave\Sabre\Helpers\Air;

use Santosdave\Sabre\Services\Rest\Air\SeatService;
use Santosdave\Sabre\Models\Air\Seat\SeatMapRequest;
use Santosdave\Sabre\Models\Air\Seat\SeatAssignRequest;
use Santosdave\Sabre\Models\Air\Seat\SeatMapResponse;
use Santosdave\Sabre\Models\Air\Seat\SeatAssignResponse;
use Santosdave\Sabre\Exceptions\SabreApiException;

class SeatHelper
{
    private SeatService $seatService;

    public function __construct(SeatService $seatService)
    {
        $this->seatService = $seatService;
    }

    public function findAvailableSeats(
        array $flightDetails,
        array $passengers,
        ?array $preferences = null
    ): array {
        try {
            // Create seat map request
            $request = new SeatMapRequest();
            $request->setTravelAgencyParty(
                $flightDetails['pseudoCityId'],
                $flightDetails['agencyId']
            );

            // Add flight segment
            $request->addFlightSegment(
                $flightDetails['origin'],
                $flightDetails['destination'],
                $flightDetails['departureDate'],
                $flightDetails['carrierCode'],
                $flightDetails['flightNumber'],
                $flightDetails['bookingClass'],
                $flightDetails['cabinType'] ?? 'Y',
                $flightDetails['operatingCarrier'] ?? null
            );

            // Add passengers
            foreach ($passengers as $passenger) {
                $request->addPassenger(
                    $passenger['id'],
                    $passenger['type'],
                    $passenger['birthDate'] ?? null,
                    $passenger['givenName'] ?? null,
                    $passenger['surname'] ?? null
                );
            }

            // Get seat map
            $response = $this->seatService->getSeatMap($request);

            if (!$response->isSuccess()) {
                throw new SabreApiException('Failed to get seat map: ' . implode(', ', $response->getErrors()));
            }

            return $this->filterSeats($response, $preferences);
        } catch (\Exception $e) {
            throw new SabreApiException('Seat search failed: ' . $e->getMessage(), $e->getCode(), null);
        }
    }

    private function filterSeats(SeatMapResponse $response, ?array $preferences = null): array
    {
        $availableSeats = [];
        $cabins = $response->getCabins();

        foreach ($cabins as $cabin) {
            foreach ($cabin['rows'] as $row) {
                foreach ($row['seats'] as $seat) {
                    if ($this->isSeatEligible($seat, $preferences)) {
                        $availableSeats[] = [
                            'number' => $seat['number'],
                            'letter' => $seat['letter'],
                            'characteristics' => $seat['characteristics'],
                            'price' => $seat['price'],
                            'cabin' => $cabin['type']
                        ];
                    }
                }
            }
        }

        return $availableSeats;
    }

    private function isSeatEligible(array $seat, ?array $preferences = null): bool
    {
        // Check if seat is available
        if ($seat['availability'] !== 'Available') {
            return false;
        }

        // If no preferences, return any available seat
        if (!$preferences) {
            return true;
        }

        // Check against preferences
        foreach ($preferences as $pref => $value) {
            switch ($pref) {
                case 'window':
                    if ($value && !in_array('Window', $seat['characteristics'])) {
                        return false;
                    }
                    break;
                case 'aisle':
                    if ($value && !in_array('Aisle', $seat['characteristics'])) {
                        return false;
                    }
                    break;
                case 'extraLegroom':
                    if ($value && !in_array('ExtraLegroom', $seat['characteristics'])) {
                        return false;
                    }
                    break;
                case 'maxPrice':
                    if (isset($seat['price']) && $seat['price']['amount'] > $value) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    public function assignSeatsWithPayment(
        string $orderId,
        array $assignments,
        array $paymentDetails
    ): SeatAssignResponse {
        try {
            // Validate assignment request first
            if (!$this->seatService->validateSeatAssignment($orderId, $assignments)) {
                throw new SabreApiException('Invalid seat assignment request');
            }

            // Create seat assignment request
            $request = new SeatAssignRequest($orderId);

            // Add seat assignments
            foreach ($assignments as $assignment) {
                $request->addSeatAssignment(
                    $assignment['passengerId'],
                    $assignment['segmentId'],
                    $assignment['seatNumber'],
                    $assignment['preferences'] ?? null
                );
            }

            // Add payment info if seat is chargeable
            $request->setPaymentCard(
                $paymentDetails['cardNumber'],
                $paymentDetails['expirationDate'],
                $paymentDetails['securityCode'],
                $paymentDetails['cardType'],
                $paymentDetails['amount'],
                $paymentDetails['currency']
            );

            // Process seat assignment
            $response = $this->seatService->assignSeats($request);

            if (!$response->isSuccess()) {
                throw new SabreApiException('Failed to assign seats: ' . implode(', ', $response->getErrors()));
            }

            return $response;
        } catch (\Exception $e) {
            throw new SabreApiException('Seat assignment failed: ' . $e->getMessage(), $e->getCode(), null);
        }
    }

    public function calculateSeatPrices(array $selectedSeats, array $passengers): array
    {
        $pricing = [];
        $totalAmount = 0;

        foreach ($selectedSeats as $seat) {
            if (isset($seat['price'])) {
                $amount = $seat['price']['amount'];
                $totalAmount += $amount;
                $pricing[] = [
                    'seatNumber' => $seat['number'],
                    'amount' => $amount,
                    'currency' => $seat['price']['currency'],
                    'characteristics' => $seat['characteristics']
                ];
            }
        }

        return [
            'details' => $pricing,
            'total' => [
                'amount' => $totalAmount,
                'currency' => $pricing[0]['currency'] ?? 'USD',
                'passengerCount' => count($passengers)
            ]
        ];
    }
}