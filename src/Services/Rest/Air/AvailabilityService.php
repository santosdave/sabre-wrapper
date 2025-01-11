<?php

namespace Santosdave\SabreWrapper\Services\Rest\Air;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\AirAvailabilityServiceInterface;
use Santosdave\SabreWrapper\Models\Air\AvailabilityRequest;
use Santosdave\SabreWrapper\Models\Air\AvailabilityResponse;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class AvailabilityService extends BaseRestService implements AirAvailabilityServiceInterface
{
    public function getAvailability(AvailabilityRequest $request): AvailabilityResponse
    {
        try {
            $response = $this->client->post(
                '/v5.3.0/shop/flights/availability',
                $request->toRestArray()
            );
            return new AvailabilityResponse($response, 'rest');
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to get availability: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getSchedules(AvailabilityRequest $request): AvailabilityResponse
    {
        try {
            $response = $this->client->post(
                '/v5.3.0/shop/flights/schedules',
                $request->toRestArray()
            );
            return new AvailabilityResponse($response, 'rest');
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to get schedules: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
