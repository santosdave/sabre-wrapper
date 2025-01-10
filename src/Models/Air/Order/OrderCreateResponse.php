<?php

namespace Santosdave\Sabre\Models\Air\Order;

use Santosdave\Sabre\Contracts\SabreResponse;

class OrderCreateResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private ?string $orderId = null;
    private ?array $order = null;
    private ?array $payments = null;
    private ?array $documents = null;

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

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function getPayments(): ?array
    {
        return $this->payments;
    }

    public function getDocuments(): ?array
    {
        return $this->documents;
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
        $this->orderId = $response['Order']['id'] ?? null;

        if (isset($response['Order'])) {
            $this->parseOrder($response['Order']);
        }

        if (isset($response['PaymentInfo'])) {
            $this->parsePayments($response['PaymentInfo']);
        }

        if (isset($response['Documents'])) {
            $this->parseDocuments($response['Documents']);
        }
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

    private function parseOrder(array $orderData): void
    {
        $this->order = [
            'id' => $orderData['id'],
            'status' => $orderData['Status'] ?? null,
            'creation' => [
                'date' => $orderData['CreateDate'] ?? null,
                'source' => $orderData['CreateSource'] ?? null
            ],
            'orderItems' => $this->parseOrderItems($orderData['OrderItems'] ?? []),
            'passengers' => $this->parsePassengers($orderData['Passengers'] ?? []),
            'contacts' => $orderData['Contacts'] ?? [],
            'price' => [
                'total' => $orderData['Price']['Total'] ?? null,
                'currency' => $orderData['Price']['Currency'] ?? null
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
                    'amount' => $item['Price']['Amount'],
                    'currency' => $item['Price']['Currency']
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
                    'carrier' => [
                        'marketing' => $segment['MarketingCarrier'],
                        'operating' => $segment['OperatingCarrier'] ?? null
                    ],
                    'flightNumber' => $segment['FlightNumber'],
                    'equipment' => $segment['Equipment'] ?? null
                ];
            }, $service['Segments'] ?? [])
        ];
    }

    private function parsePassengers(array $passengers): array
    {
        return array_map(function ($passenger) {
            return [
                'id' => $passenger['id'],
                'type' => $passenger['Type'],
                'name' => [
                    'given' => $passenger['GivenName'],
                    'surname' => $passenger['Surname']
                ],
                'birthDate' => $passenger['BirthDate'] ?? null,
                'contactInfo' => $passenger['ContactInfo'] ?? null
            ];
        }, $passengers);
    }

    private function parsePayments(array $payments): void
    {
        $this->payments = array_map(function ($payment) {
            return [
                'id' => $payment['id'],
                'type' => $payment['Type'],
                'amount' => $payment['Amount'],
                'currency' => $payment['Currency'],
                'status' => $payment['Status'],
                'method' => $payment['Method'] ?? null
            ];
        }, $payments);
    }

    private function parseDocuments(array $documents): void
    {
        $this->documents = array_map(function ($document) {
            return [
                'id' => $document['id'],
                'type' => $document['Type'],
                'number' => $document['Number'] ?? null,
                'status' => $document['Status'],
                'issuedDate' => $document['IssuedDate'] ?? null,
                'validatingCarrier' => $document['ValidatingCarrier'] ?? null
            ];
        }, $documents);
    }
}