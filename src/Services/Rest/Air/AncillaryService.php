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
                '/v1/offers/getAncillaries',
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
            $response = $this->client->get(
                "/v1/orders/{$orderId}/ancillaries/prebookable"
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
            $params = [];
            if ($segments) {
                $params['segments'] = $segments;
            }

            $response = $this->client->get(
                "/v1/orders/{$orderId}/ancillaries/postbookable",
                $params
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
                'serviceId' => $serviceId,
                'passengers' => $passengers
            ];

            if ($paymentInfo) {
                $request['paymentInfo'] = $paymentInfo;
            }

            $response = $this->client->post(
                "/v1/orders/{$orderId}/ancillaries",
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
                "/v1/orders/{$orderId}/ancillaries/{$serviceId}"
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
            $response = $this->client->get('/v1/ancillaries/rules', [
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
