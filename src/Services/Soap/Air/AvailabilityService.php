<?php

namespace Santosdave\SabreWrapper\Services\Soap\Air;

use Santosdave\SabreWrapper\Services\Base\BaseSoapService;
use Santosdave\SabreWrapper\Contracts\Services\AirAvailabilityServiceInterface;
use Santosdave\SabreWrapper\Models\Air\AvailabilityRequest;
use Santosdave\SabreWrapper\Models\Air\AvailabilityResponse;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class AvailabilityService extends BaseSoapService implements AirAvailabilityServiceInterface
{
    public function getAvailability(AvailabilityRequest $request): AvailabilityResponse
    {
        try {
            $response = $this->client->send(
                'OTA_AirAvailRQ',
                $request->toSoapArray()
            );
            return new AvailabilityResponse($response, 'soap');
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to get availability: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getSchedules(AvailabilityRequest $request): AvailabilityResponse
    {
        try {
            $response = $this->client->send(
                'AirSchedulesAndAvailabilityRQ',
                array_merge($request->toSoapArray(), ['version' => '5.3.1'])
            );
            return new AvailabilityResponse($response, 'soap');
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to get schedules: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
