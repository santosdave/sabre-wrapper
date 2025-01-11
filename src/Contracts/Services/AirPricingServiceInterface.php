<?php

namespace Santosdave\SabreWrapper\Contracts\Services;

use Santosdave\SabreWrapper\Models\Air\OfferPriceRequest;
use Santosdave\SabreWrapper\Models\Air\OfferPriceResponse;
use Santosdave\SabreWrapper\Models\Air\Pricing\PriceItineraryRequest;
use Santosdave\SabreWrapper\Models\Air\Pricing\PriceItineraryResponse;
use Santosdave\SabreWrapper\Models\Air\Pricing\ValidatePriceRequest;
use Santosdave\SabreWrapper\Models\Air\Pricing\ValidatePriceResponse;

interface AirPricingServiceInterface
{
    public function priceItinerary(PriceItineraryRequest $request): PriceItineraryResponse;
    public function validatePrice(ValidatePriceRequest $request): ValidatePriceResponse;
    public function revalidateItinerary(string $pnr): PriceItineraryResponse;
    public function getPriceQuote(string $pnr): PriceItineraryResponse;
    public function priceOffer(OfferPriceRequest $request): OfferPriceResponse;
}
