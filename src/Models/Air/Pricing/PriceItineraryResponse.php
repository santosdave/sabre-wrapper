<?php

namespace Santosdave\Sabre\Models\Air\Pricing;

use Santosdave\Sabre\Contracts\SabreResponse;

class PriceItineraryResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private array $priceQuotes = [];
    private ?array $fareInfo = null;
    private ?array $validatingCarrier = null;

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

    public function getPriceQuotes(): array
    {
        return $this->priceQuotes;
    }

    public function getFareInfo(): ?array
    {
        return $this->fareInfo;
    }

    public function getValidatingCarrier(): ?array
    {
        return $this->validatingCarrier;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        if (isset($response['OTA_AirPriceRS'])) {
            $this->parseOTAAirPriceResponse($response['OTA_AirPriceRS']);
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid response format';
        }
    }

    private function parseOTAAirPriceResponse(array $response): void
    {
        if (isset($response['ApplicationResults']['Error'])) {
            $this->success = false;
            $this->errors = array_map(function ($error) {
                return $error['SystemSpecificResults']['Message'] ?? 'Unknown error';
            }, (array) $response['ApplicationResults']['Error']);
            return;
        }

        $this->success = true;

        if (isset($response['PriceQuote'])) {
            $this->parsePriceQuotes($response['PriceQuote']);
        }

        if (isset($response['FareInfo'])) {
            $this->fareInfo = $response['FareInfo'];
        }

        if (isset($response['ValidatingCarrier'])) {
            $this->validatingCarrier = $response['ValidatingCarrier'];
        }
    }

    private function parsePriceQuotes(array $priceQuotes): void
    {
        foreach ((array) $priceQuotes as $quote) {
            $this->priceQuotes[] = [
                'passengers' => $this->parsePassengerInfo($quote['PricedItinerary'] ?? []),
                'fares' => $this->parseFareBreakdown($quote['PricedItinerary']['AirItineraryPricingInfo'] ?? []),
                'totalFare' => $this->parseTotalFare($quote['PricedItinerary']['AirItineraryPricingInfo'] ?? [])
            ];
        }
    }

    private function parsePassengerInfo(array $pricedItinerary): array
    {
        $passengers = [];
        if (isset($pricedItinerary['AirItineraryPricingInfo']['PassengerTypeQuantity'])) {
            foreach ((array) $pricedItinerary['AirItineraryPricingInfo']['PassengerTypeQuantity'] as $pax) {
                $passengers[] = [
                    'type' => $pax['Code'],
                    'quantity' => $pax['Quantity']
                ];
            }
        }
        return $passengers;
    }

    private function parseFareBreakdown(array $pricingInfo): array
    {
        $fares = [];
        if (isset($pricingInfo['FareBreakdown'])) {
            foreach ((array) $pricingInfo['FareBreakdown'] as $breakdown) {
                $fares[] = [
                    'baseFare' => $breakdown['BaseFare'] ?? null,
                    'taxes' => $breakdown['Taxes'] ?? [],
                    'fareConstruction' => $breakdown['FareConstruction'] ?? null
                ];
            }
        }
        return $fares;
    }

    private function parseTotalFare(array $pricingInfo): array
    {
        return [
            'amount' => $pricingInfo['ItinTotalFare']['TotalFare']['Amount'] ?? null,
            'currency' => $pricingInfo['ItinTotalFare']['TotalFare']['CurrencyCode'] ?? null,
            'baseAmount' => $pricingInfo['ItinTotalFare']['BaseFare']['Amount'] ?? null,
            'taxAmount' => $pricingInfo['ItinTotalFare']['Taxes']['TotalAmount'] ?? null
        ];
    }
}