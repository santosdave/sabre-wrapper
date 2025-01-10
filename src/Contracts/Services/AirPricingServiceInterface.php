<?php

namespace Santosdave\Sabre\Contracts\Services;

use Santosdave\Sabre\Models\Air\OfferPriceRequest;
use Santosdave\Sabre\Models\Air\OfferPriceResponse;
use Santosdave\Sabre\Models\Air\Pricing\PriceItineraryRequest;
use Santosdave\Sabre\Models\Air\Pricing\PriceItineraryResponse;
use Santosdave\Sabre\Models\Air\Pricing\ValidatePriceRequest;
use Santosdave\Sabre\Models\Air\Pricing\ValidatePriceResponse;

interface AirPricingServiceInterface
{
    public function priceItinerary(PriceItineraryRequest $request): PriceItineraryResponse;
    public function validatePrice(ValidatePriceRequest $request): ValidatePriceResponse;
    public function revalidateItinerary(string $pnr): PriceItineraryResponse;
    public function getPriceQuote(string $pnr): PriceItineraryResponse;
    public function priceOffer(OfferPriceRequest $request): OfferPriceResponse;
}