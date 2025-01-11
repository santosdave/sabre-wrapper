<?php

namespace Santosdave\SabreWrapper\Services\Rest\Air;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\OrderManagementServiceInterface;
use Santosdave\SabreWrapper\Models\Air\Order\OrderCreateRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderCreateResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderViewRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderViewResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderChangeRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderChangeResponse;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Models\Air\Order\OrderCancelRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderCancelResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderExchangeRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderExchangeResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderFulfillRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderFulfillResponse;
use Santosdave\SabreWrapper\Models\Air\Order\OrderSplitRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderSplitResponse;

class OrderManagementService extends BaseRestService implements OrderManagementServiceInterface
{
    public function createOrder(OrderCreateRequest $request): OrderCreateResponse
    {
        try {
            $response = $this->client->post(
                '/v1/orders/create',
                $request->toArray()
            );
            return new OrderCreateResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to create order: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function viewOrder(OrderViewRequest $request): OrderViewResponse
    {
        try {
            $response = $this->client->post(
                '/v1/orders/view',
                $request->toArray()
            );
            return new OrderViewResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to view order: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function changeOrder(OrderChangeRequest $request): OrderChangeResponse
    {
        try {
            $response = $this->client->post(
                '/v1/orders/change',
                $request->toArray()
            );
            return new OrderChangeResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to change order: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function fulfillOrder(OrderFulfillRequest $request): OrderFulfillResponse
    {
        try {
            $response = $this->client->post(
                '/v1/orders/change',
                $request->toArray()
            );
            return new OrderFulfillResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to fulfill order: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getOrderFulfillmentStatus(string $orderId): OrderFulfillResponse
    {
        try {
            $response = $this->client->post('/v1/orders/view', [
                'id' => $orderId,
                'filters' => ['fulfillment', 'payment', 'ticketing']
            ]);
            return new OrderFulfillResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get order fulfillment status: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function cancelOrder(OrderCancelRequest $request): OrderCancelResponse
    {
        try {
            $response = $this->client->post(
                '/v1/trip/orders/cancelBooking',
                $request->toArray()
            );
            return new OrderCancelResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to cancel order: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getCancelStatus(string $orderId): OrderCancelResponse
    {
        try {
            $response = $this->client->post('/v1/orders/view', [
                'id' => $orderId,
                'filters' => ['cancellation', 'refund', 'ticketing']
            ]);
            return new OrderCancelResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get cancellation status: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getBooking(string $confirmationId): OrderViewResponse
    {
        try {
            $response = $this->client->post('/v1/trip/orders/getBooking', [
                'confirmationId' => $confirmationId
            ]);
            return new OrderViewResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get booking: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function cancelBooking(
        string $confirmationId,
        bool $retrieveBooking = true,
        bool $cancelAll = true
    ): OrderCancelResponse {
        try {
            $request = new OrderCancelRequest($confirmationId);
            $request->setRetrieveBooking($retrieveBooking)
                ->setCancelAll($cancelAll);

            return $this->cancelOrder($request);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to cancel booking: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function exchangeOrder(OrderExchangeRequest $request): OrderExchangeResponse
    {
        try {
            $response = $this->client->post(
                '/v1/orders/change/exchange',
                $request->toArray()
            );
            return new OrderExchangeResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to exchange order: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getExchangeStatus(string $orderId): OrderExchangeResponse
    {
        try {
            $response = $this->client->post('/v1/orders/view', [
                'id' => $orderId,
                'filters' => ['exchange', 'payment', 'ticketing']
            ]);
            return new OrderExchangeResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get exchange status: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getExchangeQuote(string $orderId, array $newItinerary): OrderExchangeResponse
    {
        try {
            $response = $this->client->post('/v1/orders/exchange/quote', [
                'id' => $orderId,
                'newItinerary' => $newItinerary
            ]);
            return new OrderExchangeResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get exchange quote: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function confirmExchange(
        string $orderId,
        string $quoteId,
        ?array $paymentInfo = null
    ): OrderExchangeResponse {
        try {
            $request = [
                'id' => $orderId,
                'quoteId' => $quoteId
            ];

            if ($paymentInfo) {
                $request['paymentInfo'] = $paymentInfo;
            }

            $response = $this->client->post(
                '/v1/orders/exchange/confirm',
                $request
            );
            return new OrderExchangeResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to confirm exchange: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function splitOrder(OrderSplitRequest $request): OrderSplitResponse
    {
        try {
            $response = $this->client->post(
                '/v1/orders/split',
                $request->toArray()
            );
            return new OrderSplitResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to split order: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function validateSplit(string $orderId, array $splitConfig): bool
    {
        try {
            $response = $this->client->post("/v1/orders/{$orderId}/split/validate", [
                'splitConfig' => $splitConfig
            ]);
            return $response['valid'] ?? false;
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to validate order split: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getSplitOptions(string $orderId): array
    {
        try {
            return $this->client->get("/v1/orders/{$orderId}/split/options");
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get split options: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function mergeSplitOrders(array $orderIds): OrderViewResponse
    {
        try {
            $response = $this->client->post('/v1/orders/merge', [
                'orderIds' => $orderIds
            ]);
            return new OrderViewResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to merge orders: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
