<?php

namespace Santosdave\SabreWrapper\Helpers\Air;

use Santosdave\SabreWrapper\Services\Rest\Air\OrderManagementService;
use Santosdave\SabreWrapper\Models\Air\Order\OrderExchangeRequest;
use Santosdave\SabreWrapper\Models\Air\Order\OrderExchangeResponse;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Models\Air\Order\OrderViewRequest;

class ExchangeWorkflowHelper
{
    private OrderManagementService $orderService;

    public function __construct(OrderManagementService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function processExchange(
        string $orderId,
        array $itemsToExchange,
        array $newItinerary,
        array $paymentInfo = null
    ): OrderExchangeResponse {
        try {
            // Step 1: Get exchange quote
            $quoteResponse = $this->orderService->getExchangeQuote($orderId, $newItinerary);

            if (!$quoteResponse->isSuccess()) {
                throw new SabreApiException('Failed to get exchange quote: ' . implode(', ', $quoteResponse->getErrors()));
            }

            // Step 2: Create exchange request
            $exchangeRequest = new OrderExchangeRequest($orderId);

            foreach ($itemsToExchange as $itemId) {
                $exchangeRequest->addExchangeItem($itemId);
            }

            $exchangeRequest->setNewItinerary($newItinerary);

            if ($paymentInfo) {
                $exchangeRequest->setPaymentCard(
                    $paymentInfo['cardNumber'],
                    $paymentInfo['expirationDate'],
                    $paymentInfo['vendorCode'],
                    $paymentInfo['cvv'],
                    $paymentInfo['contactInfoRefId']
                );

                if (isset($paymentInfo['amount'], $paymentInfo['currency'])) {
                    $exchangeRequest->setPaymentAmount(
                        $paymentInfo['amount'],
                        $paymentInfo['currency']
                    );
                }
            }

            // Step 3: Process exchange
            $exchangeResponse = $this->orderService->exchangeOrder($exchangeRequest);

            if (!$exchangeResponse->isSuccess()) {
                throw new SabreApiException('Exchange failed: ' . implode(', ', $exchangeResponse->getErrors()));
            }

            // Step 4: Wait for and verify exchange status
            return $this->waitForExchangeCompletion($orderId);
        } catch (\Exception $e) {
            throw new SabreApiException('Exchange workflow failed: ' . $e->getMessage(), $e->getCode(), null);
        }
    }

    private function waitForExchangeCompletion(string $orderId, int $maxAttempts = 10): OrderExchangeResponse
    {
        $attempts = 0;

        do {
            $statusResponse = $this->orderService->getExchangeStatus($orderId);

            if (!$statusResponse->isSuccess()) {
                throw new SabreApiException('Failed to get exchange status: ' . implode(', ', $statusResponse->getErrors()));
            }

            $exchangeStatus = $statusResponse->getExchangeStatus();

            if ($exchangeStatus['status'] === 'COMPLETED') {
                return $statusResponse;
            }

            if ($exchangeStatus['status'] === 'FAILED') {
                throw new SabreApiException('Exchange failed: ' . ($exchangeStatus['reason'] ?? 'Unknown reason'));
            }

            $attempts++;
            sleep(2); // Wait 2 seconds before next check

        } while ($attempts < $maxAttempts);

        throw new SabreApiException('Exchange completion timeout exceeded');
    }

    public function validateExchangeEligibility(string $orderId): bool
    {
        try {
            $response = $this->orderService->viewOrder(new OrderViewRequest($orderId));

            if (!$response->isSuccess()) {
                return false;
            }

            $order = $response->getOrder();

            // Check if order status allows exchange
            if (!in_array($order['status'], ['ISSUED', 'ACTIVE'])) {
                return false;
            }

            // Check if any order items are exchangeable
            foreach ($order['orderItems'] ?? [] as $item) {
                if ($item['exchangeableStatus'] === 'EXCHANGEABLE') {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
