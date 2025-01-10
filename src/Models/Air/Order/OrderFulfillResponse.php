<?php

namespace Santosdave\Sabre\Models\Air\Order;

use Santosdave\Sabre\Contracts\SabreResponse;

class OrderFulfillResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private ?array $order = null;
    private ?array $fulfillmentStatus = null;
    private ?array $payments = null;
    private ?array $tickets = null;

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

    public function getFulfillmentStatus(): ?array
    {
        return $this->fulfillmentStatus;
    }

    public function getPayments(): ?array
    {
        return $this->payments;
    }

    public function getTickets(): ?array
    {
        return $this->tickets;
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

        if (isset($response['FulfillmentStatus'])) {
            $this->parseFulfillmentStatus($response['FulfillmentStatus']);
        }

        if (isset($response['PaymentStatus'])) {
            $this->parsePayments($response['PaymentStatus']);
        }

        if (isset($response['TicketingStatus'])) {
            $this->parseTickets($response['TicketingStatus']);
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
                    'fulfillmentStatus' => $item['FulfillmentStatus'] ?? null,
                    'paymentStatus' => $item['PaymentStatus'] ?? null,
                    'ticketingStatus' => $item['TicketingStatus'] ?? null
                ];
            }, $order['OrderItems'] ?? [])
        ];
    }

    private function parseFulfillmentStatus(array $status): void
    {
        $this->fulfillmentStatus = [
            'status' => $status['Status'],
            'completedAt' => $status['CompletedAt'] ?? null,
            'itemStatuses' => array_map(function ($item) {
                return [
                    'itemRef' => $item['OrderItemRef'],
                    'status' => $item['Status'],
                    'completedAt' => $item['CompletedAt'] ?? null
                ];
            }, $status['OrderItemStatuses'] ?? [])
        ];
    }

    private function parsePayments(array $status): void
    {
        $this->payments = array_map(function ($payment) {
            return [
                'id' => $payment['id'],
                'status' => $payment['Status'],
                'amount' => $payment['Amount'],
                'currency' => $payment['Currency'],
                'method' => $payment['Method'] ?? null,
                'authorizationCode' => $payment['AuthorizationCode'] ?? null,
                'processingDate' => $payment['ProcessingDate'] ?? null
            ];
        }, (array) $status['Payments']);
    }

    private function parseTickets(array $status): void
    {
        $this->tickets = array_map(function ($ticket) {
            return [
                'number' => $ticket['Number'],
                'passengerRef' => $ticket['PassengerRef'],
                'issueDate' => $ticket['IssueDate'],
                'validatingCarrier' => $ticket['ValidatingCarrier'],
                'status' => $ticket['Status']
            ];
        }, (array) $status['Tickets']);
    }
}