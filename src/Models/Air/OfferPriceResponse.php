<?php

namespace Santosdave\Sabre\Models\Air;

use Santosdave\Sabre\Contracts\SabreResponse;

class OfferPriceResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private ?array $offer = null;
    private ?array $pricing = null;
    private ?string $offerId = null;
    private ?string $offerItemId = null;
    private ?string $passengerId = null;

    public function __construct(array $response)
    {
        $this->parseResponse($response);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getOffer(): ?array
    {
        return $this->offer;
    }

    public function getPricing(): ?array
    {
        return $this->pricing;
    }

    public function getOfferId(): ?string
    {
        return $this->offerId;
    }

    public function getOfferItemId(): ?string
    {
        return $this->offerItemId;
    }

    public function getPassengerId(): ?string
    {
        return $this->passengerId;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        if (isset($response['Errors'])) {
            $this->success = false;
            $this->errors = $this->parseErrors($response['Errors']);
            return;
        }

        $this->success = true;

        if (isset($response['Offer'])) {
            $this->parseOffer($response['Offer']);
        }

        if (isset($response['Pricing'])) {
            $this->parsePricing($response['Pricing']);
        }

        // Extract IDs for Order Creation
        $this->offerId = $response['OfferId'] ?? null;
        $this->offerItemId = $response['SelectedOfferItem']['OfferItemId'] ?? null;
        $this->passengerId = $response['Passengers'][0]['PassengerId'] ?? null;
    }

    private function parseErrors(array $errors): array
    {
        return array_map(function ($error) {
            return [
                'code' => $error['Code'] ?? null,
                'message' => $error['Message'] ?? 'Unknown error',
                'type' => $error['Type'] ?? null
            ];
        }, (array) $errors['Error']);
    }

    private function parseOffer(array $offer): void
    {
        $this->offer = [
            'expiration' => $offer['Expiration'] ?? null,
            'items' => array_map(function ($item) {
                return [
                    'id' => $item['OfferItemId'],
                    'services' => $this->parseServices($item['Services'] ?? []),
                    'price' => $this->parsePrice($item['UnitPrice'] ?? []),
                    'rules' => $item['Rules'] ?? []
                ];
            }, (array) ($offer['OfferItems'] ?? [])),
            'owner' => $offer['Owner'] ?? null,
            'validatingCarrier' => $offer['ValidatingCarrier'] ?? null
        ];
    }

    private function parseServices(array $services): array
    {
        return array_map(function ($service) {
            return [
                'id' => $service['ServiceId'],
                'name' => $service['Name'],
                'code' => $service['Code'],
                'segment' => $service['Segment'] ?? null,
                'passenger' => $service['Passenger'] ?? null
            ];
        }, $services);
    }

    private function parsePricing(array $pricing): void
    {
        $this->pricing = [
            'currency' => $pricing['Currency'],
            'base' => [
                'amount' => $pricing['Base']['Amount'],
                'currency' => $pricing['Base']['Currency']
            ],
            'total' => [
                'amount' => $pricing['Total']['Amount'],
                'currency' => $pricing['Total']['Currency']
            ],
            'taxes' => array_map(function ($tax) {
                return [
                    'code' => $tax['Code'],
                    'amount' => $tax['Amount'],
                    'currency' => $tax['Currency']
                ];
            }, (array) ($pricing['Taxes']['Tax'] ?? [])),
            'fees' => array_map(function ($fee) {
                return [
                    'code' => $fee['Code'],
                    'amount' => $fee['Amount'],
                    'currency' => $fee['Currency']
                ];
            }, (array) ($pricing['Fees']['Fee'] ?? [])),
            'by_passenger' => $this->parsePassengerPricing($pricing['ByPassenger'] ?? [])
        ];
    }

    private function parsePrice(array $price): array
    {
        return [
            'amount' => $price['Amount'] ?? null,
            'currency' => $price['Currency'] ?? null,
            'base' => [
                'amount' => $price['Base']['Amount'] ?? null,
                'currency' => $price['Base']['Currency'] ?? null
            ]
        ];
    }

    private function parsePassengerPricing(array $passengerPricing): array
    {
        return array_map(function ($pricing) {
            return [
                'type' => $pricing['PassengerType'],
                'count' => $pricing['PassengerCount'],
                'base' => $pricing['Base']['Amount'] ?? null,
                'total' => $pricing['Total']['Amount'] ?? null,
                'taxes' => array_sum(array_column($pricing['Taxes']['Tax'] ?? [], 'Amount'))
            ];
        }, $passengerPricing);
    }
}