<?php

namespace Santosdave\Sabre\Models\Air\Seat;

use Santosdave\Sabre\Contracts\SabreResponse;

class SeatAssignResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private array $assignments = [];
    private ?array $paymentStatus = null;
    private ?array $order = null;

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

    public function getAssignments(): array
    {
        return $this->assignments;
    }

    public function getPaymentStatus(): ?array
    {
        return $this->paymentStatus;
    }

    public function getOrder(): ?array
    {
        return $this->order;
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

        if (isset($response['SeatAssignments'])) {
            $this->parseAssignments($response['SeatAssignments']);
        }

        if (isset($response['PaymentStatus'])) {
            $this->parsePaymentStatus($response['PaymentStatus']);
        }

        if (isset($response['Order'])) {
            $this->parseOrder($response['Order']);
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

    private function parseAssignments(array $assignments): void
    {
        foreach ($assignments as $assignment) {
            $this->assignments[] = [
                'passengerId' => $assignment['PassengerId'],
                'segmentId' => $assignment['SegmentId'],
                'seatNumber' => $assignment['SeatNumber'],
                'status' => $assignment['Status'],
                'characteristics' => $assignment['Characteristics'] ?? [],
                'price' => [
                    'amount' => $assignment['Price']['Amount'] ?? null,
                    'currency' => $assignment['Price']['Currency'] ?? null
                ],
                'confirmationStatus' => $assignment['ConfirmationStatus'] ?? null
            ];
        }
    }

    private function parsePaymentStatus(array $status): void
    {
        $this->paymentStatus = [
            'status' => $status['Status'],
            'amount' => [
                'paid' => $status['AmountPaid'] ?? null,
                'currency' => $status['Currency'] ?? null
            ],
            'paymentDetails' => [
                'method' => $status['PaymentMethod'] ?? null,
                'transactionId' => $status['TransactionId'] ?? null,
                'authorizationCode' => $status['AuthorizationCode'] ?? null
            ],
            'timestamp' => $status['Timestamp'] ?? null
        ];
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
                    'passengerRefs' => $item['PassengerRefs'] ?? [],
                    'seatAssignments' => array_map(function ($seat) {
                        return [
                            'number' => $seat['Number'],
                            'status' => $seat['Status'],
                            'characteristics' => $seat['Characteristics'] ?? []
                        ];
                    }, $item['SeatAssignments'] ?? [])
                ];
            }, $order['OrderItems'] ?? [])
        ];
    }
}