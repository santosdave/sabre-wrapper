<?php

namespace Santosdave\Sabre\Contracts\Services;

use Santosdave\Sabre\Models\Air\Order\OrderCancelRequest;
use Santosdave\Sabre\Models\Air\Order\OrderCancelResponse;
use Santosdave\Sabre\Models\Air\Order\OrderCreateRequest;
use Santosdave\Sabre\Models\Air\Order\OrderCreateResponse;
use Santosdave\Sabre\Models\Air\Order\OrderViewRequest;
use Santosdave\Sabre\Models\Air\Order\OrderViewResponse;
use Santosdave\Sabre\Models\Air\Order\OrderChangeRequest;
use Santosdave\Sabre\Models\Air\Order\OrderChangeResponse;
use Santosdave\Sabre\Models\Air\Order\OrderExchangeRequest;
use Santosdave\Sabre\Models\Air\Order\OrderExchangeResponse;
use Santosdave\Sabre\Models\Air\Order\OrderFulfillRequest;
use Santosdave\Sabre\Models\Air\Order\OrderFulfillResponse;

interface OrderManagementServiceInterface
{
    public function createOrder(OrderCreateRequest $request): OrderCreateResponse;
    public function viewOrder(OrderViewRequest $request): OrderViewResponse;
    public function changeOrder(OrderChangeRequest $request): OrderChangeResponse;
    public function getBooking(string $confirmationId): OrderViewResponse;

    // Cancellation methods
    public function cancelOrder(OrderCancelRequest $request): OrderCancelResponse;
    public function getCancelStatus(string $orderId): OrderCancelResponse;
    public function cancelBooking(
        string $confirmationId,
        bool $retrieveBooking = true,
        bool $cancelAll = true
    ): OrderCancelResponse;

    public function fulfillOrder(OrderFulfillRequest $request): OrderFulfillResponse;

    public function getOrderFulfillmentStatus(string $orderId): OrderFulfillResponse;


    // Exchange methods
    public function exchangeOrder(OrderExchangeRequest $request): OrderExchangeResponse;
    public function getExchangeStatus(string $orderId): OrderExchangeResponse;
    public function getExchangeQuote(string $orderId, array $newItinerary): OrderExchangeResponse;
    public function confirmExchange(
        string $orderId,
        string $quoteId,
        ?array $paymentInfo = null
    ): OrderExchangeResponse;
}