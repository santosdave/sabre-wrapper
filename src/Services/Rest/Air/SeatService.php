<?php

namespace Santosdave\SabreWrapper\Services\Rest\Air;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\SeatServiceInterface;

use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Models\Air\Seat\SeatAssignRequest;
use Santosdave\SabreWrapper\Models\Air\Seat\SeatAssignResponse;
use Santosdave\SabreWrapper\Models\Air\Seat\SeatMapRequest;
use Santosdave\SabreWrapper\Models\Air\Seat\SeatMapResponse;

class SeatService extends BaseRestService implements SeatServiceInterface
{
    public function getSeatMap(SeatMapRequest $request): SeatMapResponse
    {
        try {
            // Determine request type and source
            $requestType = $request->getRequestType();
            $source = $request->getSource();

            // Construct appropriate endpoint and payload based on request type
            switch ($requestType) {
                case 'offerId':
                    $endpoint = '/v1/offers/getseats';
                    $payload = $this->buildOfferIdRequest($request);
                    break;
                case 'orderId':
                    $endpoint = '/v1/offers/getseats';
                    $payload = $this->buildOrderIdRequest($request);
                    break;
                case 'payload':
                    $endpoint = '/v1/offers/getseats';
                    $payload = $this->buildPayloadRequest($request);
                    break;
                case 'stateless':
                    $endpoint = '/v1/offers/getseats';
                    $payload = $this->buildStatelessRequest($request);
                    break;
                default:
                    throw new SabreApiException("Unsupported seat map request type: {$requestType}");
            }

            // Make the API call
            $response = $this->client->post($endpoint, $payload);
            // Return response with source information
            return new SeatMapResponse($response, $source);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get seat map: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }


    /**
     * Build request payload for offerId type seat map
     * 
     * @param SeatMapRequest $request
     * @return array
     */
    private function buildOfferIdRequest(SeatMapRequest $request): array
    {
        $payload = [
            'requestType' => 'offerId',
            'request' => [
                'offer' => [
                    'offerId' => $request->getOfferId()
                ]
            ],
            'pointOfSale' => $this->buildPointOfSale($request)
        ];

        // Optional: Add passengers with loyalty information
        if ($passengers = $request->getPassengers()) {
            $payload['request']['paxes'] = $this->formatPassengers($passengers);
        }

        return $payload;
    }



    /**
     * Build request payload for orderId type seat map
     * 
     * @param SeatMapRequest $request
     * @return array
     */
    private function buildOrderIdRequest(SeatMapRequest $request): array
    {
        return [
            'requestType' => 'orderId',
            'request' => [
                'order' => [
                    'orderId' => $request->getOrderId()
                ]
            ],
            'pointOfSale' => $this->buildPointOfSale($request)
        ];
    }


    /**
     * Build request payload for payload type seat map
     * 
     * @param SeatMapRequest $request
     * @return array
     */
    private function buildPayloadRequest(SeatMapRequest $request): array
    {
        $payload = [
            'requestType' => 'payload',
            'request' => [
                'paxSegmentRefIds' => $request->getSegmentRefIds(),
                'originDest' => $this->buildOriginDestination($request),
                'paxes' => $this->formatPassengers($request->getPassengers())
            ],
            'pointOfSale' => $this->buildPointOfSale($request)
        ];

        // Optional fields
        if ($fareComponents = $request->getFareComponents()) {
            $payload['request']['fareComponents'] = $fareComponents;
        }

        if ($currency = $request->getCurrency()) {
            $payload['request']['currency'] = $currency;
        }

        return $payload;
    }


    /**
     * Build request payload for stateless type seat map
     * 
     * @param SeatMapRequest $request
     * @return array
     */
    private function buildStatelessRequest(SeatMapRequest $request): array
    {
        return [
            'requestType' => 'stateless',
            'request' => [
                'pnrLocator' => $request->getPnrLocator()
            ],
            'pointOfSale' => $this->buildPointOfSale($request)
        ];
    }


    /**
     * Build point of sale information
     * 
     * @param SeatMapRequest $request
     * @return array
     */
    private function buildPointOfSale(SeatMapRequest $request): array
    {
        return [
            'agentDutyCode' => $request->getAgentDutyCode() ?? '*',
            'location' => [
                'countryCode' => $request->getCountryCode() ?? 'US',
                'cityCode' => $request->getCityCode() ?? 'SFO'
            ]
        ];
    }


    /**
     * Build origin destination information
     * 
     * @param SeatMapRequest $request
     * @return array
     */
    private function buildOriginDestination(SeatMapRequest $request): array
    {
        return [
            'paxJourney' => [
                'paxSegments' => $request->getOriginDestinationSegments()
            ]
        ];
    }


    /**
     * Format passenger information for request
     * 
     * @param array $passengers
     * @return array
     */
    private function formatPassengers(array $passengers): array
    {
        return array_map(function ($passenger) {
            $formattedPassenger = [
                'paxID' => $passenger['id'],
                'ptc' => $passenger['type']
            ];

            // Optional passenger details
            if (isset($passenger['birthday'])) {
                $formattedPassenger['birthday'] = $passenger['birthday'];
            }

            if (isset($passenger['givenName'])) {
                $formattedPassenger['givenName'] = $passenger['givenName'];
            }

            if (isset($passenger['surname'])) {
                $formattedPassenger['surname'] = $passenger['surname'];
            }

            // Loyalty program information
            if (isset($passenger['loyaltyPrograms']) && is_array($passenger['loyaltyPrograms'])) {
                $formattedPassenger['loyaltyProgramAccount'] = array_map(function ($program) {
                    return [
                        'airline' => $program['airline'],
                        'accountNumber' => $program['accountNumber']
                    ];
                }, $passenger['loyaltyPrograms']);
            }

            return $formattedPassenger;
        }, $passengers);
    }


    public function getSeatMapForOrder(string $orderId): SeatMapResponse
    {
        try {
            $response = $this->client->get("/v1/orders/{$orderId}/seats");
            return new SeatMapResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get order seat map: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function assignSeats(SeatAssignRequest $request): SeatAssignResponse
    {
        try {
            $response = $this->client->post(
                "/v1/orders/{$request->toArray()['orderId']}/seats",
                $request->toArray()
            );
            return new SeatAssignResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to assign seats: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function removeSeatAssignment(
        string $orderId,
        string $passengerId,
        string $segmentId
    ): SeatAssignResponse {
        try {
            $response = $this->client->post(
                "/v1/orders/{$orderId}/seats",
                [
                    'passengerId' => $passengerId,
                    'segmentId' => $segmentId
                ]
            );
            return new SeatAssignResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to remove seat assignment: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getSeatRules(
        string $carrierCode,
        ?array $seatTypes = null
    ): array {
        try {
            $params = ['carrier' => $carrierCode];
            if ($seatTypes) {
                $params['seatTypes'] = implode(',', $seatTypes);
            }

            return $this->client->get('/v1/offers/seats/rules', $params);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get seat rules: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function validateSeatAssignment(
        string $orderId,
        array $assignments
    ): bool {
        try {
            $response = $this->client->post(
                "/v1/orders/{$orderId}/seats/validate",
                ['assignments' => $assignments]
            );
            return $response['valid'] ?? false;
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to validate seat assignments: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
