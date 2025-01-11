<?php

namespace Santosdave\SabreWrapper\Services\Rest\Air;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\ExchangeServiceInterface;
use Santosdave\SabreWrapper\Models\Air\Exchange\ExchangeSearchRequest;
use Santosdave\SabreWrapper\Models\Air\Exchange\ExchangeSearchResponse;
use Santosdave\SabreWrapper\Models\Air\Exchange\ExchangeBookRequest;
use Santosdave\SabreWrapper\Models\Air\Exchange\ExchangeBookResponse;
use Santosdave\SabreWrapper\Models\Air\Exchange\RefundQuoteRequest;
use Santosdave\SabreWrapper\Models\Air\Exchange\RefundQuoteResponse;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class ExchangeService extends BaseRestService implements ExchangeServiceInterface
{
    public function searchExchanges(ExchangeSearchRequest $request): ExchangeSearchResponse
    {
        try {
            $response = $this->client->post(
                '/v1/air/exchange/search',
                $request->toArray()
            );
            return new ExchangeSearchResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to search exchanges: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function bookExchange(ExchangeBookRequest $request): ExchangeBookResponse
    {
        try {
            $response = $this->client->post(
                '/v1/air/exchange/book',
                $request->toArray()
            );
            return new ExchangeBookResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to book exchange: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getRefundQuote(RefundQuoteRequest $request): RefundQuoteResponse
    {
        try {
            $response = $this->client->post(
                '/v1/air/refund/quote',
                $request->toArray()
            );
            return new RefundQuoteResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get refund quote: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function validateExchange(string $pnr): ExchangeSearchResponse
    {
        try {
            $response = $this->client->get("/v1/air/exchange/validate/{$pnr}");
            return new ExchangeSearchResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to validate exchange: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
