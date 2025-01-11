<?php

namespace Santosdave\SabreWrapper\Services\Rest;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\UtilityServiceInterface;
use Santosdave\SabreWrapper\Models\Utility\GeoSearchRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class UtilityService extends BaseRestService implements UtilityServiceInterface
{
    public function getAirportsAtCity(string $cityCode): array
    {
        try {
            $response = $this->client->get(
                "/v1/lists/supported/cities/{$cityCode}/airports"
            );

            return $response['Airports'] ?? [];
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get airports: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getAirlineInfo(string $airlineCode): array
    {
        try {
            $response = $this->client->get(
                '/v1/lists/utilities/airlines',
                ['airlinecode' => $airlineCode]
            );

            return $response['AirlineInfo'] ?? [];
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get airline info: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getAirlineAlliance(string $allianceCode): array
    {
        try {
            $response = $this->client->get(
                '/v1/lists/utilities/airlines/alliances',
                ['alliancecode' => $allianceCode]
            );

            return $response['AirlineAlliance'] ?? [];
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get airline alliance: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getAircraftInfo(string $aircraftCode): array
    {
        try {
            $response = $this->client->get(
                '/v1/lists/utilities/aircraft/equipment',
                ['aircraftcode' => $aircraftCode]
            );

            return $response['AircraftInfo'] ?? [];
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get aircraft info: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function geoSearch(
        string $searchTerm,
        string $category,
        ?int $radius = null,
        ?string $unit = null
    ): array {
        try {
            $request = new GeoSearchRequest($searchTerm, $category);

            if ($radius !== null) {
                $request->setRadius($radius);
            }

            if ($unit !== null) {
                $request->setUnit($unit);
            }

            $response = $this->client->post(
                '/v1.0.0/lists/utilities/geosearch/locations',
                $request->toArray()
            );

            return $response['GeoSearchRS']['GeoSearchResults'] ?? [];
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to perform geo search: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
