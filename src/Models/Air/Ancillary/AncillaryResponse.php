<?php

namespace Santosdave\SabreWrapper\Models\Air\Ancillary;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class AncillaryResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private array $services = [];
    private ?array $pricing = null;
    private ?array $availability = null;
    private ?array $rules = null;

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

    public function getServices(): array
    {
        return $this->services;
    }

    public function getPricing(): ?array
    {
        return $this->pricing;
    }

    public function getAvailability(): ?array
    {
        return $this->availability;
    }

    public function getRules(): ?array
    {
        return $this->rules;
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

        if (isset($response['Services'])) {
            $this->parseServices($response['Services']);
        }

        if (isset($response['Pricing'])) {
            $this->parsePricing($response['Pricing']);
        }

        if (isset($response['Availability'])) {
            $this->parseAvailability($response['Availability']);
        }

        if (isset($response['Rules'])) {
            $this->parseRules($response['Rules']);
        }
    }

    private function parseErrors(array $errors): array
    {
        return array_map(function ($error) {
            return [
                'code' => $error['Code'] ?? null,
                'message' => $error['Message'] ?? 'Unknown error',
                'type' => $error['Type'] ?? null,
                'details' => $error['Details'] ?? null
            ];
        }, (array) $errors['Error']);
    }

    private function parseServices(array $services): void
    {
        foreach ($services as $service) {
            $this->services[] = [
                'id' => $service['id'] ?? null,
                'code' => $service['ServiceCode'] ?? null,
                'name' => $service['ServiceName'] ?? null,
                'description' => $service['Description'] ?? null,
                'type' => $service['ServiceType'] ?? null,
                'subType' => $service['ServiceSubType'] ?? null,
                'group' => $service['Group'] ?? null,
                'segment' => [
                    'ref' => $service['SegmentRef'] ?? null,
                    'eligible' => $service['SegmentEligible'] ?? true
                ],
                'passenger' => [
                    'ref' => $service['PassengerRef'] ?? null,
                    'eligible' => $service['PassengerEligible'] ?? true
                ],
                'pricing' => $this->parseServicePricing($service['Pricing'] ?? []),
                'availability' => $this->parseServiceAvailability($service['Availability'] ?? []),
                'rules' => $service['Rules'] ?? []
            ];
        }
    }

    private function parseServicePricing(array $pricing): array
    {
        return [
            'amount' => $pricing['Amount'] ?? null,
            'currency' => $pricing['Currency'] ?? null,
            'fees' => array_map(function ($fee) {
                return [
                    'code' => $fee['Code'],
                    'amount' => $fee['Amount'],
                    'description' => $fee['Description'] ?? null
                ];
            }, $pricing['Fees'] ?? []),
            'taxes' => array_map(function ($tax) {
                return [
                    'code' => $tax['Code'],
                    'amount' => $tax['Amount']
                ];
            }, $pricing['Taxes'] ?? [])
        ];
    }

    private function parseServiceAvailability(array $availability): array
    {
        return [
            'status' => $availability['Status'] ?? null,
            'quantity' => [
                'available' => $availability['QuantityAvailable'] ?? null,
                'maximum' => $availability['MaximumQuantity'] ?? null
            ],
            'restrictions' => $availability['Restrictions'] ?? [],
            'timeLimit' => $availability['TimeLimit'] ?? null
        ];
    }

    private function parsePricing(array $pricing): void
    {
        $this->pricing = [
            'currency' => $pricing['Currency'] ?? null,
            'subtotal' => $pricing['SubTotal'] ?? null,
            'taxes' => $pricing['TotalTaxes'] ?? null,
            'total' => $pricing['TotalAmount'] ?? null,
            'breakdown' => array_map(function ($item) {
                return [
                    'type' => $item['Type'],
                    'amount' => $item['Amount'],
                    'details' => $item['Details'] ?? null
                ];
            }, $pricing['PriceBreakdown'] ?? [])
        ];
    }

    private function parseAvailability(array $availability): void
    {
        $this->availability = [
            'status' => $availability['Status'],
            'restrictions' => $availability['Restrictions'] ?? [],
            'segments' => array_map(function ($segment) {
                return [
                    'ref' => $segment['SegmentRef'],
                    'status' => $segment['Status'],
                    'available' => $segment['ServicesAvailable'] ?? []
                ];
            }, $availability['SegmentAvailability'] ?? [])
        ];
    }

    private function parseRules(array $rules): void
    {
        $this->rules = array_map(function ($rule) {
            return [
                'code' => $rule['Code'],
                'type' => $rule['Type'],
                'description' => $rule['Description'],
                'restrictions' => $rule['Restrictions'] ?? [],
                'applicability' => $rule['Applicability'] ?? []
            ];
        }, $rules);
    }
}
