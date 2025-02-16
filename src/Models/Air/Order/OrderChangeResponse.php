<?php

namespace Santosdave\SabreWrapper\Models\Air\Order;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class OrderChangeResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $warnings = [];
    private array $info = [];
    private array $data;

    // Order details
    private ?array $order = null;
    private array $processingAlerts = [];

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

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function getProcessingAlerts(): array
    {
        return $this->processingAlerts;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;
        $this->success = false;

        if (isset($response['Errors'])) {
            $this->errors = $this->parseErrors($response['Errors']);
            return;
        }

        $this->warnings = $this->parseMessages($response['warnings'] ?? [], 'warning');
        $this->info = $this->parseMessages($response['info'] ?? [], 'info');

        // Parse order details
        if (isset($response['order'])) {
            $this->order = $this->parseOrder($response['order']);
        }

        // Parse processing alerts
        if (isset($response['processingAlerts'])) {
            $this->processingAlerts = $this->parseProcessingAlerts($response['processingAlerts']);
        }

        // Determine overall success
        $this->success = empty($this->errors);
    }


    private function parseMessages(array $messages, string $type): array
    {
        return array_map(function ($message) use ($type) {
            return [
                'type' => $type,
                'code' => $message['code'] ?? null,
                'message' => $message['message'] ?? null
            ];
        }, $messages);
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

    private function parseProcessingAlerts(array $alerts): array
    {
        return array_map(function ($alert) {
            if (isset($alert['paymentAuthentication'])) {
                return [
                    'type' => 'payment_authentication',
                    'authentication_url' => $alert['paymentAuthentication']['authenticationUrl'] ?? null,
                    'transaction_id' => $alert['paymentAuthentication']['transactionId'] ?? null,
                    'supplier_transaction_id' => $alert['paymentAuthentication']['supplierTransactionId'] ?? null
                ];
            }
            return $alert;
        }, $alerts);
    }

    private function parseOrder(array $orderData): array
    {
        return [
            'id' => $orderData['id'] ?? null,
            'type' => $orderData['type'] ?? null,
            'pnr_locator' => $orderData['pnrLocator'] ?? null,
            'create_date' => $orderData['pnrCreateDate'] ?? null,
            'last_modified' => $orderData['LastModifiedDate'] ?? null,
            'status' => $orderData['Status'] ?? null,
            'order_items' => $this->parseOrderItems($orderData['orderItems'] ?? []),
            'contact_infos' => $this->parseContactInfos($orderData['contactInfos'] ?? []),
            'passengers' => $this->parsePassengers($orderData['passengers'] ?? []),
            'total_price' => $this->parsePriceDetails($orderData['totalPrice'] ?? []),
            'payment_time_limit' => $orderData['paymentTimeLimit'] ?? null
        ];
    }


    private function parseOrderItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'id' => $item['id'] ?? null,
                'status' => $item['Status'] ?? null,
                'price' => $this->parsePriceDetails($item['Price'] ?? []),
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
                    'id' => $segment['id'] ?? null,
                    'departure' => [
                        'airport' => $segment['DepartureAirport'] ?? null,
                        'time' => $segment['DepartureTime'] ?? null
                    ],
                    'arrival' => [
                        'airport' => $segment['ArrivalAirport'] ?? null,
                        'time' => $segment['ArrivalTime'] ?? null
                    ],
                    'carrier' => $segment['MarketingCarrier'] ?? null,
                    'flight_number' => $segment['FlightNumber'] ?? null
                ];
            }, $service['Segments'] ?? [])
        ];
    }

    private function parseContactInfos(array $contacts): array
    {
        return array_map(function ($contact) {
            return [
                'id' => $contact['id'] ?? null,
                'phones' => $this->parsePhones($contact['phones'] ?? []),
                'email_addresses' => $this->parseEmailAddresses($contact['emailAddresses'] ?? [])
            ];
        }, $contacts);
    }

    private function parsePassengers(array $passengers): array
    {
        return array_map(function ($passenger) {
            return [
                'id' => $passenger['id'] ?? null,
                'type_code' => $passenger['typeCode'] ?? null,
                'given_name' => $passenger['givenName'] ?? null,
                'surname' => $passenger['surname'] ?? null,
                'birthdate' => $passenger['birthdate'] ?? null
            ];
        }, $passengers);
    }

    private function parsePhones(array $phones): array
    {
        return array_map(function ($phone) {
            return [
                'id' => $phone['id'] ?? null,
                'number' => $phone['number'] ?? null,
                'country_code' => $phone['countryCode'] ?? null
            ];
        }, $phones);
    }

    private function parseEmailAddresses(array $emails): array
    {
        return array_map(function ($email) {
            return [
                'id' => $email['id'] ?? null,
                'address' => $email['address'] ?? null,
                'label' => $email['label'] ?? null
            ];
        }, $emails);
    }


    private function parsePriceDetails(array $priceDetails): ?array
    {
        if (empty($priceDetails)) {
            return null;
        }

        return [
            'amount' => $priceDetails['Amount'] ?? null,
            'currency' => $priceDetails['Currency'] ?? null
        ];
    }
}