<?php

namespace Santosdave\Sabre\Models\Air\Order;

use Santosdave\Sabre\Contracts\SabreResponse;

class OrderCancelResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private ?array $cancelStatus = null;
    private ?array $order = null;
    private ?array $refundDetails = null;
    private ?array $ticketingStatus = null;

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

    public function getCancelStatus(): ?array
    {
        return $this->cancelStatus;
    }

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function getRefundDetails(): ?array
    {
        return $this->refundDetails;
    }

    public function getTicketingStatus(): ?array
    {
        return $this->ticketingStatus;
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

        if (isset($response['CancelStatus'])) {
            $this->parseCancelStatus($response['CancelStatus']);
        }

        if (isset($response['Order'])) {
            $this->parseOrder($response['Order']);
        }

        if (isset($response['RefundDetails'])) {
            $this->parseRefundDetails($response['RefundDetails']);
        }

        if (isset($response['TicketingStatus'])) {
            $this->parseTicketingStatus($response['TicketingStatus']);
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

    private function parseCancelStatus(array $status): void
    {
        $this->cancelStatus = [
            'status' => $status['Status'],
            'timestamp' => $status['Timestamp'] ?? null,
            'cancelledItems' => array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'status' => $item['Status'],
                    'type' => $item['Type'] ?? null
                ];
            }, $status['CancelledItems'] ?? [])
        ];
    }

    private function parseOrder(array $order): void
    {
        $this->order = [
            'id' => $order['id'],
            'status' => $order['Status'],
            'items' => array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'status' => $item['Status'],
                    'cancelStatus' => $item['CancelStatus'] ?? null,
                    'refundStatus' => $item['RefundStatus'] ?? null
                ];
            }, $order['OrderItems'] ?? [])
        ];
    }

    private function parseRefundDetails(array $refund): void
    {
        $this->refundDetails = [
            'status' => $refund['Status'],
            'type' => $refund['Type'],
            'amount' => [
                'value' => $refund['Amount']['Value'],
                'currency' => $refund['Amount']['Currency']
            ],
            'paymentMethod' => $refund['PaymentMethod'] ?? null,
            'processedAt' => $refund['ProcessedAt'] ?? null,
            'refundedItems' => array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'amount' => $item['Amount'],
                    'status' => $item['Status']
                ];
            }, $refund['RefundedItems'] ?? [])
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
                    'refundStatus' => $ticket['RefundStatus'] ?? null,
                    'voidStatus' => $ticket['VoidStatus'] ?? null
                ];
            }, $status['Tickets'] ?? [])
        ];
    }
}