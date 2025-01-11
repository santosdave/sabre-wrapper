<?php

namespace Santosdave\SabreWrapper\Models\Air\Order;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class OrderSplitResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private ?string $originalOrderId = null;
    private array $newOrders = [];
    private ?array $splitStatus = null;

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

    public function getOriginalOrderId(): ?string
    {
        return $this->originalOrderId;
    }

    public function getNewOrders(): array
    {
        return $this->newOrders;
    }

    public function getSplitStatus(): ?array
    {
        return $this->splitStatus;
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
        $this->originalOrderId = $response['originalOrderId'] ?? null;

        if (isset($response['newOrders'])) {
            $this->parseNewOrders($response['newOrders']);
        }

        if (isset($response['splitStatus'])) {
            $this->parseSplitStatus($response['splitStatus']);
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

    private function parseNewOrders(array $orders): void
    {
        foreach ($orders as $order) {
            $this->newOrders[] = [
                'orderId' => $order['id'],
                'status' => $order['status'],
                'passengers' => $this->parsePassengers($order['passengers'] ?? []),
                'orderItems' => $this->parseOrderItems($order['orderItems'] ?? []),
                'contacts' => $order['contacts'] ?? [],
                'totalPrice' => [
                    'amount' => $order['totalPrice']['amount'] ?? null,
                    'currency' => $order['totalPrice']['currency'] ?? null
                ]
            ];
        }
    }

    private function parsePassengers(array $passengers): array
    {
        return array_map(function ($passenger) {
            return [
                'id' => $passenger['id'],
                'type' => $passenger['type'],
                'givenName' => $passenger['givenName'] ?? null,
                'surname' => $passenger['surname'] ?? null,
                'dateOfBirth' => $passenger['dateOfBirth'] ?? null
            ];
        }, $passengers);
    }

    private function parseOrderItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'id' => $item['id'],
                'status' => $item['status'],
                'passengerRefs' => $item['passengerRefs'] ?? [],
                'serviceDetails' => $item['serviceDetails'] ?? null,
                'price' => [
                    'amount' => $item['price']['amount'] ?? null,
                    'currency' => $item['price']['currency'] ?? null
                ]
            ];
        }, $items);
    }

    private function parseSplitStatus(array $status): void
    {
        $this->splitStatus = [
            'status' => $status['status'],
            'timestamp' => $status['timestamp'] ?? null,
            'splitItems' => array_map(function ($item) {
                return [
                    'originalItemId' => $item['originalItemId'],
                    'newItemIds' => $item['newItemIds'] ?? [],
                    'status' => $item['status']
                ];
            }, $status['splitItems'] ?? [])
        ];
    }
}
