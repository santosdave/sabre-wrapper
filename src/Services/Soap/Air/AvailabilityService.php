<?php

namespace Santosdave\Sabre\Services\Soap\Air;

use Santosdave\Sabre\Services\Base\BaseSoapService;
use Santosdave\Sabre\Contracts\Services\AirAvailabilityServiceInterface;
use Santosdave\Sabre\Models\Air\AvailabilityRequest;
use Santosdave\Sabre\Models\Air\AvailabilityResponse;
use Santosdave\Sabre\Exceptions\SabreApiException;

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