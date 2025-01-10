<?php

namespace Santosdave\Sabre\Contracts\Services;

use Santosdave\Sabre\Models\Air\BargainFinderMaxRequest;
use Santosdave\Sabre\Models\Air\BargainFinderMaxResponse;

interface AirShoppingServiceInterface
{
    public function bargainFinderMax(BargainFinderMaxRequest $request): BargainFinderMaxResponse;
    public function alternativeDatesSearch(BargainFinderMaxRequest $request): BargainFinderMaxResponse;
    public function instaFlights(
        string $origin,
        string $destination,
        string $departureDate,
        ?string $returnDate = null,
        int $limit = 50
    ): array;
}