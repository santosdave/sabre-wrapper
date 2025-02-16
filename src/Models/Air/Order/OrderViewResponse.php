<?php

namespace Santosdave\SabreWrapper\Models\Air\Order;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class OrderViewResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $warnings = [];
    private array $data;

    // Core Order Details
    private ?array $order = null;
    private ?string $orderId = null;
    private ?string $orderType = null;
    private ?string $pnrLocator = null;
    private ?string $pnrCreateDate = null;
    private ?string $offerVendor = null;
    private ?string $orderOwner = null;
    private ?string $partition = null;
    private ?string $primeHost = null;
    private ?string $countryCode = null;

    // Detailed Components
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
    private array $formsOfPayment = [];
    private array $ticketingDocumentInfo = [];

    // Additional Metadata
    private ?bool $nameMismatchedWithTickets = null;
    private ?bool $itineraryMismatchedWithTickets = null;
    private ?string $paymentTimeLimit = null;
    private ?string $priceGuaranteeTimeLimit = null;

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

    // Convenience Methods
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getPnrLocator(): ?string
    {
        return $this->pnrLocator;
    }

    public function getTotalPrice(): ?array
    {
        return $this->totalPrice;
    }

    public function getPassengers(): array
    {
        return $this->passengers;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getOrderItems(): array
    {
        return $this->orderItems;
    }

    public function getServiceDefinitions(): array
    {
        return $this->serviceDefinitions;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    // Additional Utility Methods
    public function isOrderActive(): bool
    {
        $activeStatuses = ['BOOKED', 'CONFIRMED', 'ISSUED', 'HK'];
        return $this->order &&
            isset($this->order['status']) &&
            in_array($this->order['status'], $activeStatuses);
    }

    public function getPaymentTimeLimit(): ?string
    {
        return $this->paymentTimeLimit;
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getFirstErrorMessage(): ?string
    {
        return $this->errors ? ($this->errors[0]['message'] ?? null) : null;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        // Handle errors first
        if (isset($response['Errors'])) {
            $this->success = false;
            $this->errors = $this->parseErrors($response['Errors']);
            return;
        }

        // Parse warnings
        if (isset($response['warnings'])) {
            $this->warnings = $this->parseWarnings($response['warnings']);
        }

        // Parse order details
        if (isset($response['order'])) {
            $this->success = true;
            $this->parseOrder($response['order']);
        } else {
            $this->success = false;
            $this->errors[] = ['message' => 'No order details found'];
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
        // Basic Order Information
        $this->orderId = $orderData['id'] ?? null;
        $this->orderType = $orderData['type'] ?? null;
        $this->pnrLocator = $orderData['pnrLocator'] ?? null;
        $this->pnrCreateDate = $orderData['pnrCreateDate'] ?? null;
        $this->offerVendor = $orderData['offerVendor'] ?? null;
        $this->orderOwner = $orderData['orderOwner'] ?? null;
        $this->partition = $orderData['partition'] ?? null;
        $this->primeHost = $orderData['primeHost'] ?? null;
        $this->countryCode = $orderData['countryCode'] ?? null;

        // Flags
        $this->nameMismatchedWithTickets = $orderData['nameMismatchedWithTickets'] ?? null;
        $this->itineraryMismatchedWithTickets = $orderData['itineraryMismatchedWithTickets'] ?? null;

        // Payment Details
        $this->paymentTimeLimit = $orderData['paymentTimeLimitText'] ?? null;
        $this->priceGuaranteeTimeLimit = $orderData['priceGuaranteeTimeLimitText'] ?? null;

        // Parse Complex Structures
        $parseMapping = [
            'orderItems' => 'parseOrderItems',
            'contactInfos' => 'parseContactInfos',
            'products' => 'parseProducts',
            'passengers' => 'parsePassengers',
            'journeys' => 'parseJourneys',
            'segments' => 'parseSegments',
            'priceClasses' => 'parsePriceClasses',
            'externalOrders' => 'parseExternalOrders',
            'serviceDefinitions' => 'parseServiceDefinitions',
            'baggageAllowances' => 'parseBaggageAllowances',
            'formsOfPayment' => 'parseFormsOfPayment',
            'ticketingDocumentInfo' => 'parseTicketingDocumentInfo',
            'penalties' => 'parsePenalties',
            'airlineRemarks' => 'parseAirlineRemarks'
        ];

        foreach ($parseMapping as $key => $method) {
            if (isset($orderData[$key]) && is_array($orderData[$key])) {
                $this->{$key} = $this->{$method}($orderData[$key]);
            }
        }

        // Total Price
        if (isset($orderData['totalPrice'])) {
            $this->totalPrice = $this->parsePriceDetails($orderData['totalPrice']);
        }

        // Customer Number
        if (isset($orderData['customerNumber'])) {
            $this->customerNumber = $this->parseCustomerNumber($orderData['customerNumber']);
        }
    }

    private function parseContactInfos(array $contacts): array
    {
        return array_map(function ($contact) {
            return [
                'id' => $contact['id'] ?? null,
                'given_name' => $contact['givenName'] ?? null,
                'surname' => $contact['surname'] ?? null,
                'contact_type' => $contact['contactType'] ?? null,
                'phones' => $this->parsePhones($contact['phones'] ?? []),
                'email_addresses' => $this->parseEmailAddresses($contact['emailAddresses'] ?? []),
                'postal_addresses' => $this->parsePostalAddresses($contact['postalAddresses'] ?? [])
            ];
        }, $contacts);
    }

    private function parsePassengers(array $passengers): array
    {
        return array_map(function ($passenger) {
            return [
                'id' => $passenger['id'] ?? null,
                'external_id' => $passenger['externalId'] ?? null,
                'type_code' => $passenger['typeCode'] ?? null,
                'citizenship_country' => $passenger['citizenshipCountryCode'] ?? null,
                'contact_info_refs' => $passenger['contactInfoRefIds'] ?? [],
                'age' => $passenger['age'] ?? null,
                'birthdate' => $passenger['birthdate'] ?? null,
                'given_name' => $passenger['givenName'] ?? null,
                'surname' => $passenger['surname'] ?? null,
                'gender' => $passenger['genderCode'] ?? null,
                'identity_documents' => $this->parseIdentityDocuments($passenger['identityDocuments'] ?? []),
                'loyalty_programs' => $this->parseLoyaltyPrograms($passenger['loyaltyProgramAccounts'] ?? [])
            ];
        }, $passengers);
    }

    private function parseSegments(array $segments): array
    {
        return array_map(function ($segment) {
            return [
                'id' => $segment['id'] ?? null,
                'cabin_type' => $segment['cabinTypeCode'] ?? null,
                'departure' => $this->parseLocation($segment['departure'] ?? [], 'departure'),
                'arrival' => $this->parseLocation($segment['arrival'] ?? [], 'arrival'),
                'marketing_carrier' => $this->parseCarrier($segment['marketingCarrier'] ?? []),
                'operating_carrier' => $this->parseCarrier($segment['operatingCarrier'] ?? []),
                'flight_duration' => $segment['flightDuration'] ?? null,
                'distance' => $segment['distanceMeasure'] ?? null
            ];
        }, $segments);
    }

    private function parseOrderItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'id' => $item['id'] ?? null,
                'external_id' => $item['externalId'] ?? null,
                'external_order_ref_id' => $item['externalOrderRefId'] ?? null,
                'origin' => $item['origin'] ?? null,
                'creation_date' => $item['creationDateTime'] ?? null,
                'status_code' => $item['statusCode'] ?? null,
                'price' => $this->parsePriceDetails($item['price'] ?? []),
                'services' => $this->parseServices($item['services'] ?? []),
                'offer_item_id' => $item['offerItemId'] ?? null,
                'payment_time_limit' => $item['paymentTimeLimitText'] ?? null
            ];
        }, $items);
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

    private function parsePriceDetails(array $priceDetails): ?array
    {
        if (empty($priceDetails)) {
            return null;
        }

        return [
            'base_amount' => $this->parseAmount($priceDetails['baseAmount'] ?? []),
            'total_amount' => $this->parseAmount($priceDetails['totalAmount'] ?? []),
            'total_tax_amount' => $this->parseAmount($priceDetails['totalTaxAmount'] ?? []),
            'tax_breakdowns' => $this->parseTaxBreakdowns($priceDetails['taxBreakdowns'] ?? []),
            'surcharges' => $this->parseSurcharges($priceDetails['surcharges'] ?? [])
        ];
    }

    private function parseAmount(array $amount): ?array
    {
        if (empty($amount)) {
            return null;
        }

        return [
            'amount' => $amount['amount'] ?? null,
            'currency' => $amount['code'] ?? null
        ];
    }

    private function parseTaxBreakdowns(array $taxes): array
    {
        return array_map(function ($tax) {
            return [
                'amount' => $this->parseAmount($tax['amount'] ?? []),
                'country_code' => $tax['countryCode'] ?? null,
                'tax_code' => $tax['taxCode'] ?? null,
                'description' => $tax['description'] ?? null,
                'refundable' => $tax['refundable'] ?? null
            ];
        }, $taxes);
    }

    private function parseSurcharges(array $surcharges): array
    {
        return array_map(function ($surcharge) {
            return [
                'total_amount' => $this->parseAmount($surcharge['totalAmount'] ?? []),
                'breakdown' => array_map(function ($breakdown) {
                    return [
                        'amount' => $this->parseAmount($breakdown['amount'] ?? []),
                        'local_amount' => $this->parseAmount($breakdown['localAmount'] ?? []),
                        'description' => $breakdown['description'] ?? null,
                        'refundable' => $breakdown['refundable'] ?? null
                    ];
                }, $surcharge['breakdown'] ?? [])
            ];
        }, $surcharges);
    }

    // Add more parsing methods for other complex structures
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
            'flight_number' => $carrier['flightNumber'] ?? null,
            'class_of_service' => $carrier['classOfService'] ?? null
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


    private function parseDescriptions(array $descriptions): array
    {
        return array_map(function ($description) {
            return [
                'id' => $description['id'] ?? null,
                'text' => $description['text'] ?? null
            ];
        }, $descriptions);
    }

    private function parseBookingInstructions(array $instructions): ?array
    {
        if (empty($instructions)) {
            return null;
        }

        return [
            'special_services' => $this->parseSpecialServices($instructions['specialServices'] ?? []),
            'product_text' => $instructions['productText'] ?? null,
            'product_text_details' => $this->parseProductTextDetails($instructions['productTextDetails'] ?? [])
        ];
    }

    private function parseSpecialServices(array $services): array
    {
        return array_map(function ($service) {
            return [
                'special_service_code' => $service['specialServiceCode'] ?? null,
                'free_text' => $service['freeText'] ?? null
            ];
        }, $services);
    }

    private function parseProductTextDetails(array $details): array
    {
        return array_map(function ($detail) {
            return [
                'key' => $detail['key'] ?? null,
                'value' => $detail['value'] ?? null,
                'description' => $detail['description'] ?? null
            ];
        }, $details);
    }

    private function parseBaggageAllowances(array $allowances): array
    {
        return array_map(function ($allowance) {
            return [
                'id' => $allowance['id'] ?? null,
                'external_id' => $allowance['externalId'] ?? null,
                'type_code' => $allowance['typeCode'] ?? null,
                'determining_carrier' => $this->parseDeterminingCarrier($allowance['baggageDeterminingCarrier'] ?? []),
                'applicable_party' => $allowance['applicableParty'] ?? null,
                'weight_allowances' => $this->parseWeightAllowances($allowance['weightAllowances'] ?? []),
                'dimension_allowances' => $this->parseDimensionAllowances($allowance['dimensionAllowances'] ?? []),
                'piece_allowances' => $this->parsePieceAllowances($allowance['pieceAllowances'] ?? [])
            ];
        }, $allowances);
    }

    private function parseDeterminingCarrier(array $carrier): ?array
    {
        if (empty($carrier)) {
            return null;
        }

        return [
            'carrier_code' => $carrier['carrierCode'] ?? null
        ];
    }

    private function parseWeightAllowances(array $allowances): array
    {
        return array_map(function ($allowance) {
            return [
                'maximum_measure' => $this->parseMeasure($allowance['maximumMeasure'] ?? [])
            ];
        }, $allowances);
    }

    private function parseDimensionAllowances(array $allowances): array
    {
        return array_map(function ($allowance) {
            return [
                'maximum_measure' => $this->parseMeasure($allowance['maximumMeasure'] ?? []),
                'minimum_measure' => $this->parseMeasure($allowance['minimumMeasure'] ?? []),
                'category' => $allowance['baggageDimensionCategory'] ?? null
            ];
        }, $allowances);
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

    private function parseMeasure(array $measure): ?array
    {
        if (empty($measure)) {
            return null;
        }

        return [
            'value' => $measure['value'] ?? null,
            'unit_code' => $measure['unitCode'] ?? null
        ];
    }

    private function parseFormsOfPayment(array $payments): array
    {
        return array_map(function ($payment) {
            return [
                'id' => $payment['id'] ?? null,
                'amount' => $this->parseAmount($payment['amount'] ?? []),
                'payment_method' => $this->parsePaymentMethod($payment['paymentMethod'] ?? []),
                'order_item_ref_ids' => $payment['orderItemRefIds'] ?? [],
                'use_types' => $payment['useTypes'] ?? [],
                'payer' => $this->parsePayer($payment['payer'] ?? [])
            ];
        }, $payments);
    }

    private function parsePaymentMethod(array $method): ?array
    {
        if (empty($method)) {
            return null;
        }

        return [
            'payment_card' => $this->parsePaymentCard($method['paymentCard'] ?? []),
            'payment_type_code' => $method['paymentTypeCode'] ?? null
        ];
    }

    private function parsePaymentCard(array $card): ?array
    {
        if (empty($card)) {
            return null;
        }

        return [
            'card_number' => $card['cardNumber'] ?? null,
            'vendor_code' => $card['vendorCode'] ?? null,
            'expiration_date' => $card['expirationDate'] ?? null,
            'card_holder_name' => $card['cardHolderName'] ?? null,
            'approval_code' => $card['approvalCode'] ?? null,
            'card_holder_address' => $this->parseAddress($card['cardHolderAddress'] ?? [])
        ];
    }

    private function parsePayer(array $payer): ?array
    {
        if (empty($payer)) {
            return null;
        }

        return [
            'payer_email' => $this->parsePayerEmail($payer['payerEmailAddress'] ?? []),
            'payer_name' => $this->parsePayerName($payer['payerName'] ?? []),
            'payer_phone' => $this->parsePayerPhone($payer['payerPhoneNumber'] ?? []),
            'payment_address' => $this->parseAddress($payer['paymentAddress'] ?? [])
        ];
    }

    private function parsePayerEmail(array $email): ?array
    {
        if (empty($email)) {
            return null;
        }

        return [
            'id' => $email['id'] ?? null,
            'label' => $email['label'] ?? null,
            'address' => $email['address'] ?? null
        ];
    }

    private function parsePayerName(array $name): ?array
    {
        if (empty($name)) {
            return null;
        }

        $individual = $name['individualName'] ?? [];
        return [
            'birthdate' => $individual['birthdate'] ?? null,
            'birthplace' => $individual['birthplace'] ?? null,
            'document_number' => $individual['documentNumber'] ?? null,
            'gender' => $individual['genderCode'] ?? null,
            'given_name' => $individual['givenName'] ?? null,
            'middle_name' => $individual['middleName'] ?? null,
            'suffix_name' => $individual['suffixName'] ?? null,
            'surname' => $individual['surname'] ?? null,
            'title_name' => $individual['titleName'] ?? null
        ];
    }

    private function parsePayerPhone(array $phone): ?array
    {
        if (empty($phone)) {
            return null;
        }

        return [
            'id' => $phone['id'] ?? null,
            'number' => $phone['number'] ?? null,
            'country_code' => $phone['countryCode'] ?? null,
            'city_code' => $phone['cityCode'] ?? null,
            'label' => $phone['label'] ?? null
        ];
    }

    private function parseAddress(array $address): ?array
    {
        if (empty($address)) {
            return null;
        }

        return [
            'street' => $address['street'] ?? [],
            'building_room' => $address['buildingRoom'] ?? null,
            'post_office_box' => $address['postOfficeBoxCode'] ?? null,
            'city' => $address['cityName'] ?? null,
            'state_province' => $address['stateProvinceCode'] ?? null,
            'postal_code' => $address['postalCode'] ?? null,
            'country_code' => $address['countryCode'] ?? null,
            'label' => $address['label'] ?? null
        ];
    }

    private function parseTicketingDocumentInfo(array $documents): array
    {
        return array_map(function ($doc) {
            return [
                'document_number' => $doc['document']['number'] ?? null,
                'document_type' => $doc['document']['type'] ?? null,
                'issue_date_time' => $doc['document']['issueDateTime'] ?? null,
                'ticketing_location' => $doc['document']['ticketingLocation'] ?? null,
                'price' => $this->parsePriceDetails($doc['price'] ?? []),
                'original_issue_info' => $this->parseOriginalIssueInfo($doc['originalIssueInfo'] ?? []),
                'passenger_ref_id' => $doc['paxRefId'] ?? null
            ];
        }, $documents);
    }

    private function parseOriginalIssueInfo(array $info): ?array
    {
        if (empty($info)) {
            return null;
        }

        return [
            'location_code' => $info['locationCode'] ?? null,
            'issue_date' => $info['issueDate'] ?? null,
            'ticket_number' => $info['ticketNumber'] ?? null,
            'issuing_agent_id' => $info['issuingAgentId'] ?? null
        ];
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
                'applicability_list' => $penalty['applicabilityList'] ?? [],
                'description' => $penalty['description'] ?? null
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

    private function parseCustomerNumber(array $customerNumber): ?array
    {
        if (empty($customerNumber)) {
            return null;
        }

        return [
            'number' => $customerNumber['number'] ?? null
        ];
    }

    // Remaining method stubs
    private function parseProducts(array $products): array
    {
        return array_map(function ($product) {
            return [
                'id' => $product['id'] ?? null,
                'air_segment' => $this->parseAirSegment($product['airSegment'] ?? [])
            ];
        }, $products);
    }

    private function parseAirSegment(array $segment): ?array
    {
        if (empty($segment)) {
            return null;
        }

        return [
            'marketing_carrier' => $this->parseAirlineInfo($segment['marketingCarrier'] ?? []),
            'departure_date_time' => $segment['departureDateTime'] ?? null,
            'arrival_date_time' => $segment['arrivalDateTime'] ?? null,
            'departure_airport' => $segment['departureAirport'] ?? null,
            'arrival_airport' => $segment['arrivalAirport'] ?? null,
            'action_code' => $segment['actionCode'] ?? null
        ];
    }

    private function parseAirlineInfo(array $airline): ?array
    {
        if (empty($airline)) {
            return null;
        }

        return [
            'airline_code' => $airline['airlineCode'] ?? null,
            'flight_number' => $airline['flightNumber'] ?? null,
            'booking_class' => $airline['bookingClass'] ?? null,
            'name' => $airline['name'] ?? null,
            'banner' => $airline['banner'] ?? null
        ];
    }

    private function parseJourneys(array $journeys): array
    {
        return array_map(function ($journey) {
            return [
                'id' => $journey['id'] ?? null,
                'segment_refs' => $journey['segmentRefIds'] ?? []
            ];
        }, $journeys);
    }

    private function parsePriceClasses(array $priceClasses): array
    {
        return array_map(function ($priceClass) {
            return [
                'id' => $priceClass['id'] ?? null,
                'code' => $priceClass['code'] ?? null,
                'name' => $priceClass['name'] ?? null,
                'descriptions' => $this->parseDescriptions($priceClass['descriptions'] ?? [])
            ];
        }, $priceClasses);
    }

    private function parseExternalOrders(array $externalOrders): array
    {
        return array_map(function ($order) {
            return [
                'id' => $order['id'] ?? null,
                'system_id' => $order['systemId'] ?? null,
                'external_order_id' => $order['externalOrderId'] ?? null,
                'booking_references' => $this->parseBookingReferences($order['bookingReferences'] ?? []),
                'total_price' => $this->parsePriceDetails($order['totalPrice'] ?? [])
            ];
        }, $externalOrders);
    }

    private function parseBookingReferences(array $references): array
    {
        return array_map(function ($ref) {
            return [
                'id' => $ref['id'] ?? null,
                'type_code' => $ref['typeCode'] ?? null,
                'carrier_code' => $ref['carrierCode'] ?? null,
                'create_date' => $ref['createDate'] ?? null
            ];
        }, $references);
    }

    private function parsePostalAddresses(array $addresses): array
    {
        return array_map(function ($address) {
            return [
                'street' => $address['street'] ?? [],
                'building_room' => $address['buildingRoom'] ?? null,
                'post_office_box' => $address['postOfficeBoxCode'] ?? null,
                'city' => $address['cityName'] ?? null,
                'state_province' => $address['stateProvinceCode'] ?? null,
                'postal_code' => $address['postalCode'] ?? null,
                'country_code' => $address['countryCode'] ?? null,
                'label' => $address['label'] ?? null
            ];
        }, $addresses);
    }

    private function parseIdentityDocuments(array $documents): array
    {
        return array_map(function ($doc) {
            return [
                'id' => $doc['id'] ?? null,
                'document_number' => $doc['documentNumber'] ?? null,
                'document_type_code' => $doc['documentTypeCode'] ?? null,
                'document_subtype_code' => $doc['documentSubTypeCode'] ?? null,
                'issuing_country' => $doc['issuingCountryCode'] ?? null,
                'place_of_issue' => $doc['placeOfIssue'] ?? null,
                'citizenship_country' => $doc['citizenshipCountryCode'] ?? null,
                'residence_country' => $doc['residenceCountryCode'] ?? null,
                'issue_date' => $doc['issueDate'] ?? null,
                'expiry_date' => $doc['expiryDate'] ?? null,
                'birthdate' => $doc['birthdate'] ?? null,
                'gender_code' => $doc['genderCode'] ?? null,
                'title' => $doc['titleName'] ?? null,
                'given_name' => $doc['givenName'] ?? null,
                'middle_name' => $doc['middleName'] ?? null,
                'surname' => $doc['surname'] ?? null,
                'suffix_name' => $doc['suffixName'] ?? null,
                'birthplace' => $doc['birthplace'] ?? null,
                'visa_host_country' => $doc['visaHostCountryCode'] ?? null,
                'stay_duration' => $doc['stayDuration'] ?? null,
                'entry_quantity' => $doc['entryQuantity'] ?? null,
                'referenced_document_numbers' => $doc['referencedDocumentNumbers'] ?? []
            ];
        }, $documents);
    }

    private function parseLoyaltyPrograms(array $programs): array
    {
        return array_map(function ($program) {
            return [
                'id' => $program['id'] ?? null,
                'account_number' => $program['accountNumber'] ?? null,
                'carrier' => $this->parseLoyaltyCarrier($program['carrier'] ?? []),
                'program_name' => $program['programName'] ?? null,
                'program_code' => $program['programCode'] ?? null
            ];
        }, $programs);
    }

    // Helper method for parsing carrier
    private function parseLoyaltyCarrier(array $carrier): ?array
    {
        if (empty($carrier)) {
            return null;
        }

        return [
            'carrier_code' => $carrier['carrierCode'] ?? null
        ];
    }
}