<?php

namespace Santosdave\SabreWrapper\Contracts\Services;

interface UtilityServiceInterface
{
    public function getAirportsAtCity(string $cityCode): array;
    public function getAirlineInfo(string $airlineCode): array;
    public function getAirlineAlliance(string $allianceCode): array;
    public function getAircraftInfo(string $aircraftCode): array;
    public function geoSearch(
        string $searchTerm,
        string $category,
        ?int $radius = null,
        ?string $unit = null
    ): array;
}
