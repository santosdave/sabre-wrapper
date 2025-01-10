<?php

namespace Santosdave\Sabre\Models\Air\Order;

use Santosdave\Sabre\Contracts\SabreResponse;

class OrderExchangeResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private ?array $order = null;
    private ?array $exchangeStatus = null;
    private ?array $paymentStatus = null;
    private ?array $ticketingStatus = null;
    private ?array $pricingDetails = null;

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

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function getExchangeStatus(): ?array
    {
        return $this->exchangeStatus;
    }

    public function getPaymentStatus(): ?array
    {
        return $this->paymentStatus;
    }

    public function getTicketingStatus(): ?array
    {
        return $this->ticketingStatus;
    }

    public function getPricingDetails(): ?array
    {
        return $this->pricingDetails;
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

        if (isset($response['Order'])) {
            $this->parseOrder($response['Order']);
        }

        if (isset($response['ExchangeStatus'])) {
            $this->parseExchangeStatus($response['ExchangeStatus']);
        }

        if (isset($response['PaymentStatus'])) {
            $this->parsePaymentStatus($response['PaymentStatus']);
        }

        if (isset($response['TicketingStatus'])) {
            $this->parseTicketingStatus($response['TicketingStatus']);
        }

        if (isset($response['PricingDetails'])) {
            $this->parsePricingDetails($response['PricingDetails']);
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

    private function parseOrder(array $order): void
    {
        $this->order = [
            'id' => $order['id'],
            'status' => $order['Status'],
            'orderItems' => array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'status' => $item['Status'],
                    'exchangeStatus' => $item['ExchangeStatus'] ?? null,
                    'ticketingStatus' => $item['TicketingStatus'] ?? null
                ];
            }, $order['OrderItems'] ?? [])
        ];
    }

    private function parseExchangeStatus(array $status): void
    {
        $this->exchangeStatus = [
            'status' => $status['Status'],
            'completedAt' => $status['CompletedAt'] ?? null,
            'exchangedItems' => array_map(function ($item) {
                return [
                    'originalItemId' => $item['OriginalItemId'],
                    'newItemId' => $item['NewItemId'],
                    'status' => $item['Status']
                ];
            }, $status['ExchangedItems'] ?? [])
        ];
    }

    private function parsePaymentStatus(array $status): void
    {
        $this->paymentStatus = [
            'status' => $status['Status'],
            'amount' => [
                'paid' => $status['AmountPaid'] ?? null,
                'refunded' => $status['AmountRefunded'] ?? null,
                'currency' => $status['Currency'] ?? null
            ],
            'transactions' => array_map(function ($transaction) {
                return [
                    'id' => $transaction['id'],
                    'type' => $transaction['Type'],
                    'amount' => $transaction['Amount'],
                    'status' => $transaction['Status']
                ];
            }, $status['Transactions'] ?? [])
        ];
    }

    private function parseTicketingStatus(array $status): void
    {
        $this->ticketingStatus = [
            'status' => $status['Status'],
            'tickets' => array_map(function ($ticket) {
                return [
                    'number' => $ticket['Number'],
                    'status' => $ticket['Status'],
                    'exchangeStatus' => $ticket['ExchangeStatus'] ?? null,
                    'validatingCarrier' => $ticket['ValidatingCarrier'] ?? null,
                    'couponDetails' => $ticket['CouponDetails'] ?? []
                ];
            }, $status['Tickets'] ?? [])
        ];
    }

    private function parsePricingDetails(array $pricing): void
    {
        $this->pricingDetails = [
            'exchangeFee' => [
                'amount' => $pricing['ExchangeFee']['Amount'] ?? null,
                'currency' => $pricing['ExchangeFee']['Currency'] ?? null
            ],
            'fareDifference' => [
                'amount' => $pricing['FareDifference']['Amount'] ?? null,
                'currency' => $pricing['FareDifference']['Currency'] ?? null
            ],
            'totalAmount' => [
                'amount' => $pricing['TotalAmount']['Amount'] ?? null,
                'currency' => $pricing['TotalAmount']['Currency'] ?? null
            ],
            'taxes' => array_map(function ($tax) {
                return [
                    'code' => $tax['Code'],
                    'amount' => $tax['Amount'],
                    'currency' => $tax['Currency']
                ];
            }, $pricing['Taxes'] ?? [])
        ];
    }
}