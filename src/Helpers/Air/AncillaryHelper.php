<?php

namespace Santosdave\Sabre\Helpers\Air;

use Santosdave\Sabre\Services\Rest\Air\AncillaryService;
use Santosdave\Sabre\Models\Air\Ancillary\AncillaryRequest;
use Santosdave\Sabre\Models\Air\Ancillary\AncillaryResponse;
use Santosdave\Sabre\Exceptions\SabreApiException;

class AncillaryHelper
{
    private AncillaryService $ancillaryService;

    public function __construct(AncillaryService $ancillaryService)
    {
        $this->ancillaryService = $ancillaryService;
    }

    public function getAvailableAncillaries(
        array $flightDetails,
        array $passengers,
        string $currency = 'USD'
    ): array {
        try {
            // Create request for ancillaries
            $request = new AncillaryRequest();
            $request->setTravelAgencyParty(
                $flightDetails['pseudoCityId'],
                $flightDetails['agencyId']
            );

            // Add flight segment
            foreach ($flightDetails['segments'] as $index => $segment) {
                $request->addFlightSegment(
                    "segment{$index}",
                    $segment['origin'],
                    $segment['destination'],
                    $segment['departureDate'],
                    $segment['carrierCode'],
                    $segment['flightNumber'],
                    $segment['bookingClass'],
                    $segment['operatingCarrier'] ?? null
                );
            }

            // Add passengers
            foreach ($passengers as $index => $passenger) {
                $request->addPassenger(
                    "passenger{$index}",
                    $passenger['type'],
                    $passenger['birthDate'] ?? null,
                    $passenger['givenName'] ?? null,
                    $passenger['surname'] ?? null
                );
            }

            $request->setCurrency($currency);

            // Get ancillaries
            $response = $this->ancillaryService->getAncillaries($request);

            if (!$response->isSuccess()) {
                throw new SabreApiException('Failed to get ancillaries: ' . implode(', ', $response->getErrors()));
            }

            return $this->categorizeAncillaries($response->getServices());
        } catch (\Exception $e) {
            throw new SabreApiException('Ancillary search failed: ' . $e->getMessage(), $e->getCode(), null);
        }
    }

    private function categorizeAncillaries(array $services): array
    {
        $categories = [
            'baggage' => [],
            'seats' => [],
            'meals' => [],
            'other' => []
        ];

        foreach ($services as $service) {
            switch ($service['type']) {
                case 'A':
                    $categories['baggage'][] = $service;
                    break;
                case 'S':
                    $categories['seats'][] = $service;
                    break;
                case 'M':
                    $categories['meals'][] = $service;
                    break;
                default:
                    $categories['other'][] = $service;
            }
        }

        return $categories;
    }

    public function addAncillaryWithPayment(
        string $orderId,
        string $serviceId,
        array $passengers,
        array $paymentDetails
    ): AncillaryResponse {
        try {
            // Validate payment details
            $this->validatePaymentDetails($paymentDetails);

            // Format payment info
            $paymentInfo = [
                'amount' => $paymentDetails['amount'],
                'currency' => $paymentDetails['currency'],
                'method' => [
                    'card' => [
                        'number' => $paymentDetails['cardNumber'],
                        'expiry' => $paymentDetails['expiryDate'],
                        'code' => $paymentDetails['securityCode'],
                        'type' => $paymentDetails['cardType']
                    ]
                ]
            ];

            // Add ancillary to order
            return $this->ancillaryService->addAncillaryToOrder(
                $orderId,
                $serviceId,
                $passengers,
                $paymentInfo
            );
        } catch (\Exception $e) {
            throw new SabreApiException('Failed to add ancillary with payment: ' . $e->getMessage(), $e->getCode(), null);
        }
    }

    private function validatePaymentDetails(array $details): void
    {
        $required = ['amount', 'currency', 'cardNumber', 'expiryDate', 'securityCode', 'cardType'];

        foreach ($required as $field) {
            if (empty($details[$field])) {
                throw new SabreApiException("Missing required payment field: {$field}");
            }
        }

        // Add additional validation as needed
        if (!preg_match('/^\d{16}$/', $details['cardNumber'])) {
            throw new SabreApiException('Invalid card number format');
        }

        if (!preg_match('/^\d{2}\/\d{2}$/', $details['expiryDate'])) {
            throw new SabreApiException('Invalid expiry date format (MM/YY required)');
        }
    }
}