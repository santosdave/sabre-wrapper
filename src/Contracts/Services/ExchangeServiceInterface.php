<?php

namespace Santosdave\SabreWrapper\Contracts\Services;

use Santosdave\SabreWrapper\Models\Air\Exchange\ExchangeSearchRequest;
use Santosdave\SabreWrapper\Models\Air\Exchange\ExchangeSearchResponse;
use Santosdave\SabreWrapper\Models\Air\Exchange\ExchangeBookRequest;
use Santosdave\SabreWrapper\Models\Air\Exchange\ExchangeBookResponse;
use Santosdave\SabreWrapper\Models\Air\Exchange\RefundQuoteRequest;
use Santosdave\SabreWrapper\Models\Air\Exchange\RefundQuoteResponse;

interface ExchangeServiceInterface
{
    public function searchExchanges(ExchangeSearchRequest $request): ExchangeSearchResponse;
    public function bookExchange(ExchangeBookRequest $request): ExchangeBookResponse;
    public function getRefundQuote(RefundQuoteRequest $request): RefundQuoteResponse;
    public function validateExchange(string $pnr): ExchangeSearchResponse;
}
