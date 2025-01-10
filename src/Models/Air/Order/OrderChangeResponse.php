<?php

namespace Santosdave\Sabre\Models\Air\Order;

use Santosdave\Sabre\Contracts\SabreResponse;

class OrderChangeResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private ?array $order = null;
    private ?array $changeResult = null;
    private ?array $fulfillmentResult = null;
    private ?array $paymentStatus = null;
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

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function getChangeResult(): ?array
    {
        return $this->changeResult;
    }

    public function getFulfillmentResult(): ?array
    {
        return $this->fulfillmentResult;
    }

    public function getPaymentStatus(): ?array
    {
        return $this->paymentStatus;
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

        if (isset($response['Order'])) {
            $this->parseOrder($response['Order']);
        }

        if (isset($response['ChangeResult'])) {
            $this->parseChangeResult($response['ChangeResult']);
        }

        if (isset($response['FulfillmentResult'])) {
            $this->parseFulfillmentResult($response['FulfillmentResult']);
        }

        if (isset($response['PaymentStatus'])) {
            $this->parsePaymentStatus($response['PaymentStatus']);
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

    private function parseOrder(array $order): void
    {
        $this->order = [
            'id' => $order['id'],
            'status' => $order['Status'],
            'lastModified' => $order['LastModifiedDate'] ?? null,
            'items' => $this->parseOrderItems($order['OrderItems'] ?? []),
            'price' => [
                'total' => $order['TotalPrice']['Amount'] ?? null,
                'currency' => $order['TotalPrice']['Currency'] ?? null
            ]
        ];
    }

    private function parseOrderItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'id' => $item['id'],
                'status' => $item['Status'],
                'price' => [
                    'amount' => $item['Price']['Amount'] ?? null,
                    'currency' => $item['Price']['Currency'] ?? null
                ],
                'service' => $this->parseService($item['Service'] ?? [])
            ];
        }, $items);
    }

    private function parseService(array $service): array
    {
        return [
            'id' => $service['id'] ?? null,
            'type' => $service['Type'] ?? null,
            'status' => $service['Status'] ?? null,
            'segments' => array_map(function ($segment) {
                return [
                    'id' => $segment['id'],
                    'departure' => [
                        'airport' => $segment['DepartureAirport'],
                        'time' => $segment['DepartureTime']
                    ],
                    'arrival' => [
                        'airport' => $segment['ArrivalAirport'],
                        'time' => $segment['ArrivalTime']
                    ],
                    'carrier' => $segment['MarketingCarrier'],
                    'flightNumber' => $segment['FlightNumber']
                ];
            }, $service['Segments'] ?? [])
        ];
    }

    private function parseChangeResult(array $result): void
    {
        $this->changeResult = [
            'status' => $result['Status'],
            'type' => $result['Type'],
            'affectedItems' => array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'status' => $item['Status'],
                    'changeType' => $item['ChangeType']
                ];
            }, $result['AffectedItems'] ?? [])
        ];
    }

    private function parseFulfillmentResult(array $result): void
    {
        $this->fulfillmentResult = [
            'status' => $result['Status'],
            'payments' => array_map(function ($payment) {
                return [
                    'id' => $payment['id'],
                    'status' => $payment['Status'],
                    'amount' => $payment['Amount'],
                    'currency' => $payment['Currency']
                ];
            }, $result['Payments'] ?? []),
            'documents' => array_map(function ($document) {
                return [
                    'id' => $document['id'],
                    'type' => $document['Type'],
                    'number' => $document['Number'],
                    'status' => $document['Status']
                ];
            }, $result['Documents'] ?? [])
        ];
    }

    private function parsePaymentStatus(array $status): void
    {
        $this->paymentStatus = [
            'status' => $status['Status'],
            'authorizationCode' => $status['AuthorizationCode'] ?? null,
            'paymentMethod' => $status['PaymentMethod'] ?? null,
            'amount' => [
                'value' => $status['Amount']['Value'] ?? null,
                'currency' => $status['Amount']['Currency'] ?? null
            ]
        ];
    }

    private function parseTicketingStatus(array $status): void
    {
        $this->ticketingStatus = [
            'status' => $status['Status'],
            'ticketNumbers' => $status['TicketNumbers'] ?? [],
            'issuedBy' => $status['IssuedBy'] ?? null,
            'issuedAt' => $status['IssuedAt'] ?? null,
            'validatingCarrier' => $status['ValidatingCarrier'] ?? null
        ];
    }
}