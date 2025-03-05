<?php

namespace Santosdave\SabreWrapper\Services\Rest\Air;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\AncillaryServiceInterface;
use Santosdave\SabreWrapper\Models\Air\Ancillary\AncillaryRequest;
use Santosdave\SabreWrapper\Models\Air\Ancillary\AncillaryResponse;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class AncillaryService extends BaseRestService implements AncillaryServiceInterface
{
    public function getAncillaries(AncillaryRequest $request): AncillaryResponse
    {
        try {
            $response = $this->client->post(
                '/v2/offers/getAncillaries',
                $request->toArray()
            );
            return new AncillaryResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get ancillaries: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getPrebookableAncillaries(string $orderId): AncillaryResponse
    {
        try {
            $request = new AncillaryRequest();
            $request->setOrderId($orderId);

            // Add flag for prebookable services
            $response = $this->client->post(
                '/v2/offers/getAncillaries',
                array_merge(
                    $request->toArray(),
                    ['ancillaryType' => 'PREBOOKABLE']
                )
            );
            return new AncillaryResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get prebookable ancillaries: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getPostbookableAncillaries(
        string $orderId,
        ?array $segments = null
    ): AncillaryResponse {
        try {
            $request = new AncillaryRequest();
            $request->setOrderId($orderId);

            // Add segments if provided
            if ($segments) {
                foreach ($segments as $segmentRef) {
                    $request->addRequestedSegmentRef($segmentRef);
                }
            }

            $response = $this->client->post(
                '/v2/offers/getAncillaries',
                array_merge(
                    $request->toArray(),
                    ['ancillaryType' => 'POSTBOOKABLE']
                )
            );
            return new AncillaryResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get postbookable ancillaries: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function addAncillaryToOrder(
        string $orderId,
        string $serviceId,
        array $passengers,
        ?array $paymentInfo = null
    ): AncillaryResponse {
        try {
            $request = [
                'orderId' => $orderId,
                'serviceRequest' => [
                    'serviceId' => $serviceId,
                    'passengers' => $passengers
                ]
            ];

            // Add optional payment information
            if ($paymentInfo) {
                $request['paymentInfo'] = $paymentInfo;
            }

            $response = $this->client->post(
                '/v2/offers/addAncillaryToOrder',
                $request
            );
            return new AncillaryResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to add ancillary to order: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function removeAncillaryFromOrder(
        string $orderId,
        string $serviceId
    ): AncillaryResponse {
        try {
            $response = $this->client->post(
                '/v2/offers/removeAncillaryFromOrder',
                [
                    'orderId' => $orderId,
                    'serviceId' => $serviceId
                ]
            );
            return new AncillaryResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to remove ancillary from order: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getAncillaryRules(
        string $serviceCode,
        string $carrierCode
    ): AncillaryResponse {
        try {
            $response = $this->client->get('/v2/offers/ancillaryRules', [
                'serviceCode' => $serviceCode,
                'carrierCode' => $carrierCode
            ]);
            return new AncillaryResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get ancillary rules: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
