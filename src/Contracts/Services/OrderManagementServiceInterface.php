<?php

namespace Santosdave\SabreWrapper\Contracts\Services;

use Santosdave\SabreWrapper\Models\Air\Order\OrderCancelRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderCancelResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderCreateRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderCreateResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderViewRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderViewResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderChangeRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderChangeResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderExchangeRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderExchangeResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderFulfillRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderFulfillResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderSplitRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderSplitResponse;

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

    /**
     * Split an existing order into multiple orders
     */
    public function splitOrder(OrderSplitRequest $request): OrderSplitResponse;

    /**
     * Validate if an order can be split with given configuration
     */
    public function validateSplit(string $orderId, array $splitConfig): bool;

    /**
     * Get available split options for an order
     */
    public function getSplitOptions(string $orderId): array;

    /**
     * Merge previously split orders back together
     */
    public function mergeSplitOrders(array $orderIds): OrderViewResponse;


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
