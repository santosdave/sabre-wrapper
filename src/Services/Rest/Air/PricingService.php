<?php

namespace Santosdave\SabreWrapper\Services\Rest\Air;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\AirPricingServiceInterface;
use Santosdave\SabreWrapper\Models\Air\Pricing\PriceItineraryRequest;
use Santosdave\SabreWrapper\Models\Air\Pricing\PriceItineraryResponse;
use Santosdave\SabreWrapper\Models\Air\Pricing\ValidatePriceRequest;
use Santosdave\SabreWrapper\Models\Air\Pricing\ValidatePriceResponse;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Models\Air\OfferPriceRequest;
use Santosdave\SabreWrapper\Models\Air\OfferPriceResponse;

class PricingService extends BaseRestService implements AirPricingServiceInterface
{
    public function priceItinerary(PriceItineraryRequest $request): PriceItineraryResponse
    {
        try {
            $response = $this->client->post(
                '/v2/air/price',
                $request->toArray()
            );
            return new PriceItineraryResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to price itinerary: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function priceOffer(OfferPriceRequest $request): OfferPriceResponse
    {
        try {
            $response = $this->client->post(
                '/v1/offers/price',
                $request->toArray()
            );
            return new OfferPriceResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to price NDC offer: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function validatePrice(ValidatePriceRequest $request): ValidatePriceResponse
    {
        try {
            $response = $this->client->post(
                '/v2/air/price/validate',
                $request->toArray()
            );
            return new ValidatePriceResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to validate price: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function revalidateItinerary(string $pnr): PriceItineraryResponse
    {
        try {
            $response = $this->client->post("/v2/air/price/revalidate/{$pnr}");
            return new PriceItineraryResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to revalidate itinerary: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getPriceQuote(string $pnr): PriceItineraryResponse
    {
        try {
            $response = $this->client->get("/v2/air/price/quote/{$pnr}");
            return new PriceItineraryResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to get price quote: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
