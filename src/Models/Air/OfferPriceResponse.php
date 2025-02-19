<?php

namespace Santosdave\SabreWrapper\Models\Air;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class OfferPriceResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private ?array $offer = null;

    private ?array $allOffers = null;
    private ?array $pricing = null;
    private ?string $offerId = null;
    private ?string $offerItemId = null;
    private ?string $passengerId = null;
    private ?array $payloadAttributes = null;
    private ?array $messages = null;

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

    // Add a new method to get all offers
    public function getAllOffers(): array
    {
        return $this->allOffers ?? [];
    }

    public function getOffer(): ?array
    {
        return $this->offer;
    }

    public function getPricing(): ?array
    {
        return $this->pricing;
    }

    public function getOfferId(): ?string
    {
        return $this->offerId;
    }

    public function getOfferItemId(): ?string
    {
        return $this->offerItemId;
    }

    public function getPassengerId(): ?string
    {
        return $this->passengerId;
    }

    public function getPayloadAttributes(): ?array
    {
        return $this->payloadAttributes;
    }

    public function getMessages(): ?array
    {
        return $this->messages;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        // Check for errors
        if (isset($response['Errors'])) {
            $this->errors = $this->parseErrors($response['Errors']);
            return;
        }

        // Parse messages (warnings, notifications)
        if (isset($response['messages'])) {
            $this->messages = array_map(function ($message) {
                return [
                    'type' => $message['type'] ?? null,
                    'message' => $message['message'] ?? null,
                    'service' => $message['service'] ?? null
                ];
            }, $response['messages']);
        }


        // Parse payload attributes
        if (isset($response['payloadAttributes'])) {
            $this->payloadAttributes = [
                'timestamp' => $response['payloadAttributes']['timeStamp'] ?? null,
                'transaction_id' => $response['payloadAttributes']['trxID'] ?? null,
                'host' => $response['payloadAttributes']['host'] ?? null,
                'baseline' => $response['payloadAttributes']['baseline'] ?? null
            ];
        }

        // Check NDC response structure
        $responseData = $response['response'] ?? $response;
        if (isset($responseData['offers'])) {
            $this->success = true;
            $this->parseOffers($responseData['offers']);
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid response format';
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

    private function parseOffers(array $offers): void
    {
        if (empty($offers)) {
            $this->success = false;
            $this->errors[] = 'No offers found';
            return;
        }

        // Parse all offers, but set only the first one as the primary offer
        $this->allOffers = array_map(function ($offer) {
            return [
                'id' => $offer['id'] ?? null,
                'ttl' => $offer['ttl'] ?? null,
                'source' => $offer['source'] ?? null,
                'expiration' => $offer['offerExpirationDateTime'] ?? null,
                'payment_time_limit' => $offer['paymentTimeLimitDateTime'] ?? null,
                'journeys' => $this->parseJourneys($offer['journeys'] ?? []),
                'offer_items' => $this->parseOfferItems($offer['offerItems'] ?? []),
                'total_price' => $offer['totalPrice']['totalAmount'] ?? null
            ];
        }, $offers);

        // Set the first offer as the primary offer
        $this->offer = $this->allOffers[0];

        // Parse the first offer's details for backward compatibility
        $firstOffer = $offers[0];


        if (!empty($firstOffer['offerItems'])) {
            $offerItem = $firstOffer['offerItems'][0];

            // Parse passengers
            $passengers = $this->parsePricePassengers($offerItem['passengers'] ?? []);

            $this->pricing = [
                'total_price' => $offerItem['price']['totalAmount'] ?? null,
                'passengers' => $passengers
            ];

            // Extract key identifiers
            $this->offerId = $firstOffer['id'] ?? null;
            $this->offerItemId = $offerItem['id'] ?? null;

            // Get first passenger ID if available
            $this->passengerId = $passengers[0]['id'] ?? null;
        }
    }

    private function parseJourneys(array $journeys): array
    {
        return array_map(function ($journey) {
            return [
                'segment_refs' => $journey['segmentRefIds'] ?? [],
                'price_class_ref' => $journey['priceClassRefId'] ?? null
            ];
        }, $journeys);
    }

    private function parseOfferItems(array $offerItems): array
    {
        return array_map(function ($offerItem) {
            return [
                'id' => $offerItem['id'] ?? null,
                'mandatory_indicator' => $offerItem['mandatoryInd'] ?? null,
                'commission' => $this->parseCommission($offerItem['commission'] ?? []),
                'passengers' => $this->parsePricePassengers($offerItem['passengers'] ?? []),
                'price' => $this->parseOfferItemPrice($offerItem['price'] ?? [])
            ];
        }, $offerItems);
    }

    private function parseCommission(array $commission): ?array
    {
        if (empty($commission)) {
            return null;
        }

        return [
            'percentage' => $commission['percentage'] ?? null,
            'code' => $commission['code'] ?? null
        ];
    }

    private function parseOfferItemPrice(array $price): ?array
    {
        if (empty($price)) {
            return null;
        }

        return [
            'total_amount' => [
                'amount' => $price['totalAmount']['amount'] ?? null,
                'currency' => $price['totalAmount']['curCode'] ?? null
            ]
        ];
    }


    private function parsePricePassengers(array $passengers): array
    {
        return array_map(function ($passenger) {
            return [
                'id' => $passenger['id'] ?? null,
                'type' => $passenger['ptc'] ?? null,
                'requested_type' => $passenger['requestedPtc'] ?? null,
                'baggage' => $this->parseBaggage($passenger['baggage'] ?? []),
                'services' => $this->parsePassengerServices($passenger['services'] ?? []),
                'price' => $this->parsePassengerPrice($passenger['price'] ?? []),
                'fare_components' => $this->parseFareComponents($passenger['fareComponents'] ?? [])
            ];
        }, $passengers);
    }

    private function parseBaggage(array $baggage): array
    {
        return array_map(function ($bag) {
            return [
                'type' => $bag['type'] ?? null,
                'applicable_party' => $bag['applicablePartyText'] ?? null,
                'segments' => $bag['segments'] ?? [],
                'quantity' => $bag['details'][0]['quantity'] ?? null
            ];
        }, $baggage);
    }

    private function parsePassengerServices(array $services): array
    {
        return array_map(function ($service) {
            return [
                'name' => $service['name'] ?? null,
                'segments' => $service['segments'] ?? []
            ];
        }, $services);
    }

    private function parsePassengerPrice(array $price): array
    {
        return [
            'total_amount' => $price['totalAmount'] ?? null,
            'base_amount' => $price['baseAmount'] ?? null,
            'taxes' => $this->parseTaxes($price['taxes'] ?? []),
            'filing_information' => $this->parseFilingInformation($price['filingInformation'] ?? [])
        ];
    }

    private function parseTaxes(array $taxes): array
    {
        if (!isset($taxes['total'], $taxes['breakdown'])) {
            return [];
        }

        return [
            'total' => $taxes['total'] ?? null,
            'breakdown' => array_map(function ($tax) {
                return [
                    'amount' => $tax['amount'] ?? null,
                    'tax_code' => $tax['taxCode'] ?? null,
                    'description' => $tax['description'] ?? null,
                    'nation' => $tax['nation'] ?? null
                ];
            }, $taxes['breakdown'])
        ];
    }

    private function parseFilingInformation(array $filingInfo): ?array
    {
        if (empty($filingInfo)) {
            return null;
        }

        return [
            'base_amount' => $filingInfo['baseAmount'] ?? null,
            'exchange_rate' => $filingInfo['exchangeRate'] ?? null
        ];
    }

    private function parseFareComponents(array $fareComponents): array
    {
        return array_map(function ($component) {
            return [
                'price' => $this->parseFareComponentPrice($component['price'] ?? []),
                'fare_basis' => $this->parseFareBasis($component['fareBasis'] ?? []),
                'fare_rules' => $this->parseFareRules($component['fareRules'] ?? []),
                'segments' => $this->parseFareSegments($component['segments'] ?? []),
                'brand' => $this->parseBrand($component['brand'] ?? []),
                'voluntary_change_info' => $this->parseVoluntaryChangeInfo($component['voluntaryChangeInformation'] ?? [])
            ];
        }, $fareComponents);
    }


    private function parseFareComponentPrice(array $price): ?array
    {
        if (empty($price)) {
            return null;
        }

        return [
            'base_amount' => [
                'amount' => $price['baseAmount']['amount'] ?? null,
                'currency' => $price['baseAmount']['curCode'] ?? null
            ],
            'filing_information' => $this->parseFilingInformation($price['filingInformation'] ?? []),
            'taxes' => $this->parseTaxes($price['taxes'] ?? [])
        ];
    }

    private function parseFareBasis(array $fareBasis): ?array
    {
        if (empty($fareBasis)) {
            return null;
        }

        return [
            'fare_basis_code' => $fareBasis['fareBasisCode'] ?? null,
            'fare_description' => $fareBasis['fareDescription'] ?? null,
            'fare_code' => $fareBasis['fareCode'] ?? null,
            'fare_basis_city_pair' => $fareBasis['fareBasisCityPair'] ?? null,
            'rbd' => $fareBasis['rbd'] ?? null,
            'cabin_type' => $this->parseCabinType($fareBasis['cabinType'] ?? []),
            'sabre_cabin_type' => $this->parseCabinType($fareBasis['sabreCabinType'] ?? [])
        ];
    }

    private function parseCabinType(array $cabinType): ?array
    {
        if (empty($cabinType)) {
            return null;
        }

        return [
            'cabin_type_code' => $cabinType['cabinTypeCode'] ?? null,
            'cabin_type_name' => $cabinType['cabinTypeName'] ?? null
        ];
    }

    private function parseFareRules(array $fareRules): ?array
    {
        if (empty($fareRules)) {
            return null;
        }

        return [
            'penalty' => $this->parsePenalties($fareRules['penalty'] ?? []),
            'ticketing' => $this->parseTicketing($fareRules['ticketing'] ?? [])
        ];
    }

    private function parsePenalties(array $penalties): array
    {
        // First, extract the top-level indicators
        $indicators = [
            'cancel_fee_allowed' => $penalties['cancelFeeInd'] ?? false,
            'change_fee_allowed' => $penalties['changeFeeInd'] ?? false,
            'refundable' => $penalties['refundableInd'] ?? false
        ];

        // Group penalties by type
        $parsedPenalties = [
            'cancel' => [],
            'change' => [],
            'refund' => []
        ];

        // Process individual penalty details
        $details = $penalties['details'] ?? [];
        foreach ($details as $penalty) {
            $penaltyType = strtolower($penalty['penaltyType']);

            // Handle metadata (instructions)
            if (isset($penalty['metadata'])) {
                $metadata = $penalty['metadata'][0] ?? [];
                $indicators["{$penaltyType}_instruction"] =
                    $metadata['values'][0]['instruction'] ?? null;
            }

            // Handle amounts
            if (isset($penalty['amounts'])) {
                $penaltyAmounts = array_map(function ($amount) {
                    return [
                        'amount' => $amount['currencyAmountValue']['amount'] ?? null,
                        'currency' => $amount['currencyAmountValue']['curCode'] ?? null,
                        'taxable' => $amount['currencyAmountValue']['taxable'] ?? null,
                        'amount_application' => $amount['amountApplication'] ?? null,
                        'application' => $penalty['application'] ?? null
                    ];
                }, $penalty['amounts']);

                // Add to appropriate penalty type
                $parsedPenalties[$penaltyType] = array_merge(
                    $parsedPenalties[$penaltyType],
                    $penaltyAmounts
                );
            }
        }

        return [
            'indicators' => $indicators,
            'penalties' => $parsedPenalties
        ];
    }

    private function parseTicketing(array $ticketing): ?array
    {
        if (empty($ticketing)) {
            return null;
        }

        return [
            'endorsements' => $ticketing['endorsements'] ?? []
        ];
    }

    private function parseFareSegments(array $segments): array
    {
        return array_map(function ($segment) {
            return [
                'id' => $segment['id'] ?? null,
                'rbd' => $segment['rbd'] ?? null,
                'flight_number' => $segment['flightNumber'] ?? null,
                'marketing_carrier' => [
                    'code' => $segment['marketingCarrier'] ?? null,
                    'name' => $segment['marketingCarrierName'] ?? null
                ],
                'operating_carrier' => [
                    'code' => $segment['operatingCarrier'] ?? null,
                    'name' => $segment['operatingCarrierName'] ?? null
                ],
                'cabin_type' => $this->parseCabinType($segment['cabinType'] ?? []),
                'departure' => $this->parseLocation($segment['departure'] ?? [], 'departure'),
                'arrival' => $this->parseLocation($segment['arrival'] ?? [], 'arrival'),
                'duration' => $segment['duration'] ?? null,
                'equipment' => $segment['equipment'] ?? null,
                'distance_in_miles' => $segment['distanceInMiles'] ?? null
            ];
        }, $segments);
    }

    private function parseLocation(array $location, string $type): ?array
    {
        if (empty($location)) {
            return null;
        }

        return [
            'airport' => $location['airport'] ?? null,
            'date' => $location['date'] ?? null,
            'terminal' => $location[$type . 'Terminal'] ?? null
        ];
    }

    private function parseBrand(array $brand): ?array
    {
        if (empty($brand)) {
            return null;
        }

        return [
            'code' => $brand['code'] ?? null,
            'brand_name' => $brand['brandName'] ?? null,
            'descriptions' => array_map(function ($description) {
                return [
                    'id' => $description['id'] ?? null,
                    'text' => $description['text'] ?? null
                ];
            }, $brand['descriptions'] ?? [])
        ];
    }

    private function parseVoluntaryChangeInfo(array $changeInfo): array
    {
        return array_map(function ($change) {
            return [
                'type' => $change['type'] ?? null,
                'is_allowed' => $change['isAllowed'] ?? true,
                'has_fee' => $change['hasFee'] ?? false,
                'fee_amount' => $change['feeAmount'] ?? null,
                'fee_currency' => $change['feeCurrencyCode'] ?? null,
                'applicability' => $change['applicabilityList'] ?? []
            ];
        }, $changeInfo);
    }
}
