<?php

namespace Santosdave\SabreWrapper\Models\Air\Exchange;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class ExchangeSearchResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private array $exchangeOptions = [];
    private ?array $priceInfo = null;

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

    public function getExchangeOptions(): array
    {
        return $this->exchangeOptions;
    }

    public function getPriceInfo(): ?array
    {
        return $this->priceInfo;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        if (isset($response['OTA_AirExchangeRS'])) {
            $this->parseExchangeResponse($response['OTA_AirExchangeRS']);
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid response format';
        }
    }

    private function parseExchangeResponse(array $response): void
    {
        if (isset($response['Errors'])) {
            $this->success = false;
            $this->errors = $this->parseErrors($response['Errors']);
            return;
        }

        $this->success = true;

        if (isset($response['ExchangeOptions'])) {
            $this->parseExchangeOptions($response['ExchangeOptions']);
        }

        if (isset($response['PricingInformation'])) {
            $this->parsePricingInfo($response['PricingInformation']);
        }
    }

    private function parseErrors(array $errors): array
    {
        return array_map(function ($error) {
            return $error['ErrorMessage'] ?? 'Unknown error';
        }, (array) $errors['Error']);
    }

    private function parseExchangeOptions(array $options): void
    {
        foreach ((array) $options['Option'] as $option) {
            $this->exchangeOptions[] = [
                'itinerary' => $this->parseItinerary($option['NewItinerary'] ?? []),
                'pricing' => $this->parsePricing($option['Pricing'] ?? []),
                'penalties' => $this->parsePenalties($option['Penalties'] ?? [])
            ];
        }
    }

    private function parseItinerary(array $itinerary): array
    {
        $segments = [];
        if (isset($itinerary['FlightSegment'])) {
            foreach ((array) $itinerary['FlightSegment'] as $segment) {
                $segments[] = [
                    'carrier' => $segment['MarketingAirline']['Code'],
                    'flightNumber' => $segment['FlightNumber'],
                    'departure' => [
                        'airport' => $segment['DepartureAirport']['LocationCode'],
                        'time' => $segment['DepartureDateTime']
                    ],
                    'arrival' => [
                        'airport' => $segment['ArrivalAirport']['LocationCode'],
                        'time' => $segment['ArrivalDateTime']
                    ],
                    'bookingClass' => $segment['ResBookDesigCode']
                ];
            }
        }
        return $segments;
    }

    private function parsePricing(array $pricing): array
    {
        return [
            'baseFare' => [
                'amount' => $pricing['BaseFare']['Amount'] ?? null,
                'currency' => $pricing['BaseFare']['CurrencyCode'] ?? null
            ],
            'totalFare' => [
                'amount' => $pricing['TotalFare']['Amount'] ?? null,
                'currency' => $pricing['TotalFare']['CurrencyCode'] ?? null
            ],
            'taxes' => $this->parseTaxes($pricing['Taxes'] ?? [])
        ];
    }

    private function parseTaxes(array $taxes): array
    {
        $parsedTaxes = [];
        if (isset($taxes['Tax'])) {
            foreach ((array) $taxes['Tax'] as $tax) {
                $parsedTaxes[] = [
                    'code' => $tax['TaxCode'],
                    'amount' => $tax['Amount'],
                    'currency' => $tax['CurrencyCode']
                ];
            }
        }
        return $parsedTaxes;
    }

    private function parsePricingInfo(array $pricingInfo): void
    {
        $this->priceInfo = [
            'baseFare' => [
                'amount' => $pricingInfo['BaseFare']['Amount'] ?? null,
                'currency' => $pricingInfo['BaseFare']['CurrencyCode'] ?? null
            ],
            'totalFare' => [
                'amount' => $pricingInfo['TotalFare']['Amount'] ?? null,
                'currency' => $pricingInfo['TotalFare']['CurrencyCode'] ?? null
            ],
            'taxes' => $this->parseTaxes($pricingInfo['Taxes'] ?? [])
        ];
    }

    private function parsePenalties(array $penalties): array
    {
        return array_map(function ($penalty) {
            return [
                'type' => $penalty['Type'],
                'amount' => $penalty['Amount'],
                'currency' => $penalty['CurrencyCode']
            ];
        }, (array) ($penalties['Penalty'] ?? []));
    }
}
