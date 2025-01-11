<?php

namespace Santosdave\SabreWrapper\Services\Soap;

use Santosdave\SabreWrapper\Services\Base\BaseSoapService;
use Santosdave\SabreWrapper\Contracts\Services\UtilityServiceInterface;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class UtilityService extends BaseSoapService implements UtilityServiceInterface
{
    public function getAirportsAtCity(string $cityCode): array
    {
        throw new SabreApiException('This functionality is only available via REST API');
    }

    public function getAirlineInfo(string $airlineCode): array
    {
        throw new SabreApiException('This functionality is only available via REST API');
    }

    public function getAirlineAlliance(string $allianceCode): array
    {
        throw new SabreApiException('This functionality is only available via REST API');
    }

    public function getAircraftInfo(string $aircraftCode): array
    {
        throw new SabreApiException('This functionality is only available via REST API');
    }

    public function geoSearch(
        string $searchTerm,
        string $category,
        ?int $radius = null,
        ?string $unit = null
    ): array {
        throw new SabreApiException('This functionality is only available via REST API');
    }
}
