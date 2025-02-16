<?php

namespace Santosdave\SabreWrapper\Models\Air\Order;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class OrderCreateResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $warnings = [];
    private array $data;

    // Order details
    private ?array $order = null;
    private ?string $orderId = null;
    private ?string $pnrLocator = null;
    private ?string $pnrCreateDate = null;
    private ?string $orderType = null;
    private ?string $offerVendor = null;
    private ?string $orderOwner = null;
    private ?string $partition = null;
    private ?string $primeHost = null;
    private ?string $countryCode = null;

    // Detailed components
    private array $orderItems = [];
    private array $contactInfos = [];
    private array $products = [];
    private array $passengers = [];
    private array $journeys = [];
    private array $segments = [];
    private array $priceClasses = [];
    private ?array $customerNumber = null;
    private array $externalOrders = [];
    private ?array $totalPrice = null;
    private array $penalties = [];
    private array $airlineRemarks = [];
    private array $serviceDefinitions = [];
    private array $baggageAllowances = [];

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

    public function getData(): array
    {
        return $this->data;
    }

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getPnrLocator(): ?string
    {
        return $this->pnrLocator;
    }

    public function getOrderItems(): array
    {
        return $this->orderItems;
    }

    public function getPassengers(): array
    {
        return $this->passengers;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        if (isset($response['Errors'])) {
            $this->success = false;
            $this->errors = $this->parseErrors($response['Errors']);
            return;
        }

        // Handle warnings
        if (isset($response['warnings'])) {
            $this->warnings = $this->parseWarnings($response['warnings']);
        }

        if (isset($response['order'])) {
            $this->success = true;
            $this->parseOrder($response['order']);
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid response format';
        }
    }

    private function parseWarnings(array $warnings): array
    {
        return array_map(function ($warning) {
            return [
                'code' => $warning['code'] ?? null,
                'message' => $warning['message'] ?? null
            ];
        }, $warnings);
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
        // Basic order information
        $this->orderId = $orderData['id'] ?? null;
        $this->orderType = $orderData['type'] ?? null;
        $this->pnrLocator = $orderData['pnrLocator'] ?? null;
        $this->pnrCreateDate = $orderData['pnrCreateDate'] ?? null;
        $this->offerVendor = $orderData['offerVendor'] ?? null;
        $this->orderOwner = $orderData['orderOwner'] ?? null;
        $this->partition = $orderData['partition'] ?? null;
        $this->primeHost = $orderData['primeHost'] ?? null;
        $this->countryCode = $orderData['countryCode'] ?? null;

        // Parse order details with dedicated methods
        if (isset($orderData['orderItems'])) {
            $this->orderItems = $this->parseOrderItems($orderData['orderItems']);
        }

        if (isset($orderData['contactInfos'])) {
            $this->contactInfos = $this->parseContactInfos($orderData['contactInfos']);
        }

        if (isset($orderData['products'])) {
            $this->products = $this->parseProducts($orderData['products']);
        }

        if (isset($orderData['journeys'])) {
            $this->products = $this->parseJourneys($orderData['journeys']);
        }

        if (isset($orderData['passengers'])) {
            $this->passengers = $this->parsePassengers($orderData['passengers']);
        }

        if (isset($orderData['segments'])) {
            $this->segments = $this->parseSegments($orderData['segments']);
        }

        if (isset($orderData['priceClasses'])) {
            $this->priceClasses = $this->parsePriceClasses($orderData['priceClasses']);
        }

        if (isset($orderData['customerNumber'])) {
            $this->customerNumber = $this->parseCustomerNumber($orderData['customerNumber']);
        }

        if (isset($orderData['externalOrders'])) {
            $this->externalOrders = $this->parseExternalOrders($orderData['externalOrders']);
        }

        if (isset($orderData['totalPrice'])) {
            $this->totalPrice = $this->parsePriceDetails($orderData['totalPrice']);
        }

        if (isset($orderData['penalties'])) {
            $this->penalties = $this->parsePenalties($orderData['penalties']);
        }

        if (isset($orderData['airlineRemarks'])) {
            $this->airlineRemarks = $this->parseAirlineRemarks($orderData['airlineRemarks']);
        }

        if (isset($orderData['serviceDefinitions'])) {
            $this->serviceDefinitions = $this->parseServiceDefinitions($orderData['serviceDefinitions']);
        }

        if (isset($orderData['baggageAllowances'])) {
            $this->baggageAllowances = $this->parseBaggageAllowances($orderData['baggageAllowances']);
        }

        // Final order structure for quick access
        $this->order = [
            'id' => $this->orderId,
            'type' => $this->orderType,
            'pnr_locator' => $this->pnrLocator,
            'order_items' => $this->orderItems,
            'passengers' => $this->passengers,
            'total_price' => $this->totalPrice
        ];
    }

    private function parseOrderItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'id' => $item['id'] ?? null,
                'origin' => $item['origin'] ?? null,
                'item_origin' => $item['itemOrigin'] ?? null,
                'creation_date' => $item['creationDateTime'] ?? null,
                'external_id' => $item['externalId'] ?? null,
                'external_order_ref_id' => $item['externalOrderRefId'] ?? null,
                'offer_item_id' => $item['offerItemId'] ?? null,
                'external_offer_item_id' => $item['externalOfferItemId'] ?? null,
                'payment_timelimit' => $item['paymentTimeLimitText'] ?? null,
                'status_code' => $item['statusCode'] ?? null,
                'fare_details' => $item['fareDetails'] ?? null,
                'price' => $this->parsePriceDetails($item['price'] ?? []),
                'services' => $this->parseServices($item['services'] ?? [])
            ];
        }, $items);
    }

    private function parseContactInfos(array $contactInfos): array
    {
        return array_map(function ($contact) {
            return [
                'id' => $contact['id'] ?? null,
                'phones' => $this->parsePhones($contact['phones'] ?? []),
                'email_addresses' => $this->parseEmailAddresses($contact['emailAddresses'] ?? [])
            ];
        }, $contactInfos);
    }

    private function parseProducts(array $products): array
    {
        return array_map(function ($product) {
            return [
                'id' => $product['id'] ?? null,
                'air_segment' => $this->parseAirSegment($product['airSegment'] ?? [])
            ];
        }, $products);
    }

    private function parsePassengers(array $passengers): array
    {
        return array_map(function ($passenger) {
            return [
                'id' => $passenger['id'] ?? null,
                'external_id' => $passenger['externalId'] ?? null,
                'type_code' => $passenger['typeCode'] ?? null,
                'given_name' => $passenger['givenName'] ?? null,
                'surname' => $passenger['surname'] ?? null,
                'birthdate' => $passenger['birthdate'] ?? null,
                'contact_info_ref_ids' => $passenger['contactInfoRefIds'] ?? []
            ];
        }, $passengers);
    }

    private function parseSegments(array $segments): array
    {
        return array_map(function ($segment) {
            return [
                'id' => $segment['id'] ?? null,
                'cabin_type' => $segment['cabinTypeCode'] ?? null,
                'flight_duration' => $segment['flightDuration'] ?? null,
                'operating_legs' => $segment['datedOperatingLegs'] ?? null,
                'distance' => $segment['distanceMeasure'] ?? null,
                'departure' => $this->parseLocation($segment['departure'] ?? [], 'departure'),
                'arrival' => $this->parseLocation($segment['arrival'] ?? [], 'arrival'),
                'marketing_carrier' => $this->parseCarrier($segment['marketingCarrier'] ?? []),
                'operating_carrier' => $this->parseCarrier($segment['operatingCarrier'] ?? [])
            ];
        }, $segments);
    }

    private function parsePriceClasses(array $priceClasses): array
    {
        return array_map(function ($priceClass) {
            return [
                'id' => $priceClass['id'] ?? null,
                'code' => $priceClass['code'] ?? null,
                'name' => $priceClass['name'] ?? null
            ];
        }, $priceClasses);
    }

    private function parseCustomerNumber(array $customerNumber): ?array
    {
        return $customerNumber['number'] ?? null
            ? ['number' => $customerNumber['number']]
            : null;
    }

    private function parseExternalOrders(array $externalOrders): array
    {
        return array_map(function ($order) {
            return [
                'id' => $order['id'] ?? null,
                'system_id' => $order['systemId'] ?? null,
                'external_order_id' => $order['externalOrderId'] ?? null,
                'booking_references' => $order['bookingReferences'] ?? []
            ];
        }, $externalOrders);
    }

    private function parsePenalties(array $penalties): array
    {
        return array_map(function ($penalty) {
            return [
                'id' => $penalty['id'] ?? null,
                'type' => $penalty['type'] ?? null,
                'is_allowed' => $penalty['isAllowed'] ?? null,
                'has_fee' => $penalty['hasFee'] ?? null,
                'fee_amount' => $penalty['feeAmount'] ?? null,
                'fee_currency' => $penalty['feeCurrencyCode'] ?? null,
                'applicability_list' => $penalty['applicabilityList'] ?? []
            ];
        }, $penalties);
    }

    private function parseAirlineRemarks(array $remarks): array
    {
        return array_map(function ($remark) {
            return [
                'id' => $remark['id'] ?? null,
                'text' => $remark['text'] ?? null,
                'passenger_ref_ids' => $remark['passengerRefIds'] ?? []
            ];
        }, $remarks);
    }

    private function parseServiceDefinitions(array $services): array
    {
        return array_map(function ($service) {
            return [
                'id' => $service['id'] ?? null,
                'external_id' => $service['externalId'] ?? null,
                'name' => $service['name'] ?? null,
                'service_code' => $service['serviceCode'] ?? null,
                'reason_for_issuance_code' => $service['reasonForIssuanceCode'] ?? null,
                'reason_for_issuance_subcode' => $service['reasonForIssuanceSubCode'] ?? null,
                'descriptions' => $service['descriptions'] ?? null,
                'settlement_method_code' => $service['settlementMethodCode'] ?? null,
            ];
        }, $services);
    }

    private function parseBaggageAllowances(array $allowances): array
    {
        return array_map(function ($allowance) {
            return [
                'id' => $allowance['id'] ?? null,
                'external_id' => $allowance['externalId'] ?? null,
                'type_code' => $allowance['typeCode'] ?? null,
                'applicable_party' => $allowance['applicableParty'] ?? null,
                'baggage_determining_carrier' => $allowance['baggageDeterminingCarrier'] ?? null,
                'piece_allowances' => $this->parsePieceAllowances($allowance['pieceAllowances'] ?? [])
            ];
        }, $allowances);
    }

    private function parseLocation(array $location, string $type): ?array
    {
        if (empty($location)) {
            return null;
        }

        return [
            'location_code' => $location['locationCode'] ?? null,
            'station_name' => $location['stationName'] ?? null,
            'terminal_name' => $location['terminalName'] ?? null,
            'scheduled_date_time' => $location['scheduledDateTime'] ?? null
        ];
    }

    private function parseCarrier(array $carrier): ?array
    {
        if (empty($carrier)) {
            return null;
        }

        return [
            'carrier_code' => $carrier['carrierCode'] ?? null,
            'carrier_name' => $carrier['carrierName'] ?? null,
            'flight_number' => $carrier['flightNumber'] ?? null
        ];
    }

    private function parseServices(array $services): array
    {
        return array_map(function ($service) {
            return [
                'id' => $service['id'] ?? null,
                'external_id' => $service['externalId'] ?? null,
                'passenger_ref_id' => $service['passengerRefId'] ?? null,
                'segment_ref_id' => $service['segmentRefId'] ?? null,
                'service_definition_ref_id' => $service['serviceDefinitionRefId'] ?? null,
                'external_order_item_id' => $service['externalOrderItemId'] ?? null,
            ];
        }, $services);
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
                'address' => $email['address'] ?? null
            ];
        }, $emails);
    }

    private function parseAirSegment(array $segment): ?array
    {
        if (empty($segment)) {
            return null;
        }

        return [
            'marketing_carrier' => $this->parseCarrier($segment['marketingCarrier'] ?? []),
            'departure_date_time' => $segment['departureDateTime'] ?? null,
            'arrival_date_time' => $segment['arrivalDateTime'] ?? null,
            'departure_airport' => $segment['departureAirport'] ?? null,
            'arrival_airport' => $segment['arrivalAirport'] ?? null,
            'action_code' => $segment['actionCode'] ?? null,
        ];
    }

    private function parsePieceAllowances(array $allowances): array
    {
        return array_map(function ($allowance) {
            return [
                'applicable_party' => $allowance['applicableParty'] ?? null,
                'total_quantity' => $allowance['totalQuantity'] ?? null
            ];
        }, $allowances);
    }

    private function parsePriceDetails(array $priceDetails): ?array
    {
        if (empty($priceDetails)) {
            return null;
        }

        return [
            'base_amount' => $this->parseAmount($priceDetails['baseAmount'] ?? []),
            'total_amount' => $this->parseAmount($priceDetails['totalAmount'] ?? []),
            'total_tax_amount' => $this->parseAmount($priceDetails['totalTaxAmount'] ?? [])
        ];
    }

    private function parseAmount(array $amountDetails): ?array
    {
        if (empty($amountDetails)) {
            return null;
        }

        return [
            'amount' => $amountDetails['amount'] ?? null,
            'currency' => $amountDetails['code'] ?? null
        ];
    }

    private function parseJourneys(array $journeys): array
    {
        return array_map(function ($journey) {
            return [
                'id' => $journey['id'] ?? null,
                'segment_refs' => $journey['segmentRefIds'] ?? [],
            ];
        }, $journeys);
    }
}