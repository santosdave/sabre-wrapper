<?php

namespace Santosdave\Sabre\Services\Rest\Air;

use Santosdave\Sabre\Services\Base\BaseRestService;
use Santosdave\Sabre\Contracts\Services\AirAvailabilityServiceInterface;
use Santosdave\Sabre\Models\Air\AvailabilityRequest;
use Santosdave\Sabre\Models\Air\AvailabilityResponse;
use Santosdave\Sabre\Exceptions\SabreApiException;

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