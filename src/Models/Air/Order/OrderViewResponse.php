<?php

namespace Santosdave\Sabre\Models\Air\Order;

use Santosdave\Sabre\Contracts\SabreResponse;

class OrderViewResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private ?array $order = null;
    private ?array $payments = null;
    private ?array $documents = null;
    private ?array $bookingDetails = null;

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

    public function getPayments(): ?array
    {
        return $this->payments;
    }

    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    public function getBookingDetails(): ?array
    {
        return $this->bookingDetails;
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

        if (isset($response['PaymentInfo'])) {
            $this->parsePayments($response['PaymentInfo']);
        }

        if (isset($response['Documents'])) {
            $this->parseDocuments($response['Documents']);
        }

        if (isset($response['Booking'])) {
            $this->parseBookingDetails($response['Booking']);
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

    private function parseOrder(array $order): void
    {
        $this->order = [
            'id' => $order['id'],
            'status' => $order['Status'],
            'createdAt' => $order['CreateDate'] ?? null,
            'lastModified' => $order['LastModifiedDate'] ?? null,
            'orderItems' => $this->parseOrderItems($order['OrderItems'] ?? []),
            'passengers' => $this->parsePassengers($order['Passengers'] ?? []),
            'contacts' => $order['Contacts'] ?? [],
            'remarks' => $order['Remarks'] ?? [],
            'totalPrice' => [
                'amount' => $order['TotalPrice']['Amount'] ?? null,
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
                'passengerRefs' => $item['PassengerRefs'] ?? [],
                'service' => [
                    'id' => $item['Service']['id'] ?? null,
                    'type' => $item['Service']['Type'] ?? null,
                    'segments' => $this->parseSegments($item['Service']['Segments'] ?? [])
                ],
                'price' => [
                    'amount' => $item['Price']['Amount'] ?? null,
                    'currency' => $item['Price']['Currency'] ?? null
                ]
            ];
        }, $items);
    }

    private function parseSegments(array $segments): array
    {
        return array_map(function ($segment) {
            return [
                'id' => $segment['id'],
                'departure' => [
                    'airport' => $segment['DepartureAirport'],
                    'terminal' => $segment['DepartureTerminal'] ?? null,
                    'date' => $segment['DepartureDate'],
                    'time' => $segment['DepartureTime']
                ],
                'arrival' => [
                    'airport' => $segment['ArrivalAirport'],
                    'terminal' => $segment['ArrivalTerminal'] ?? null,
                    'date' => $segment['ArrivalDate'],
                    'time' => $segment['ArrivalTime']
                ],
                'marketingCarrier' => [
                    'code' => $segment['MarketingCarrier']['Code'],
                    'flightNumber' => $segment['MarketingCarrier']['FlightNumber']
                ],
                'operatingCarrier' => $segment['OperatingCarrier'] ?? null,
                'equipment' => $segment['Equipment'] ?? null,
                'status' => $segment['Status'] ?? null
            ];
        }, $segments);
    }

    private function parsePassengers(array $passengers): array
    {
        return array_map(function ($passenger) {
            return [
                'id' => $passenger['id'],
                'type' => $passenger['Type'],
                'givenName' => $passenger['GivenName'],
                'surname' => $passenger['Surname'],
                'dateOfBirth' => $passenger['DateOfBirth'] ?? null,
                'gender' => $passenger['Gender'] ?? null,
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
                'status' => $payment['Status'],
                'amount' => $payment['Amount'],
                'currency' => $payment['Currency'],
                'method' => $payment['Method'] ?? null,
                'cardDetails' => $payment['CardDetails'] ?? null
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
                'passengerRef' => $document['PassengerRef'] ?? null,
                'issueDate' => $document['IssueDate'] ?? null,
                'status' => $document['Status'] ?? null,
                'validatingCarrier' => $document['ValidatingCarrier'] ?? null
            ];
        }, $documents);
    }

    private function parseBookingDetails(array $booking): void
    {
        $this->bookingDetails = [
            'recordLocator' => $booking['RecordLocator'] ?? null,
            'status' => $booking['Status'] ?? null,
            'createdAt' => $booking['CreateDate'] ?? null,
            'modifiedAt' => $booking['ModifyDate'] ?? null,
            'segments' => $this->parseSegments($booking['Segments'] ?? []),
            'passengers' => $this->parsePassengers($booking['Passengers'] ?? []),
            'remarks' => $booking['Remarks'] ?? []
        ];
    }
}