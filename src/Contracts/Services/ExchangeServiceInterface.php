<?php

namespace Santosdave\Sabre\Contracts\Services;

use Santosdave\Sabre\Models\Air\Exchange\ExchangeSearchRequest;
use Santosdave\Sabre\Models\Air\Exchange\ExchangeSearchResponse;
use Santosdave\Sabre\Models\Air\Exchange\ExchangeBookRequest;
use Santosdave\Sabre\Models\Air\Exchange\ExchangeBookResponse;
use Santosdave\Sabre\Models\Air\Exchange\RefundQuoteRequest;
use Santosdave\Sabre\Models\Air\Exchange\RefundQuoteResponse;

interface ExchangeServiceInterface
{
    public function searchExchanges(ExchangeSearchRequest $request): ExchangeSearchResponse;
    public function bookExchange(ExchangeBookRequest $request): ExchangeBookResponse;
    public function getRefundQuote(RefundQuoteRequest $request): RefundQuoteResponse;
    public function validateExchange(string $pnr): ExchangeSearchResponse;
}