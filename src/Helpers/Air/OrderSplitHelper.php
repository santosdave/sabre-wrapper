<?php

namespace Santosdave\Sabre\Helpers\Air;

use Santosdave\Sabre\Services\Rest\Air\OrderManagementService;
use Santosdave\Sabre\Models\Air\Order\OrderSplitRequest;
use Santosdave\Sabre\Models\Air\Order\OrderSplitResponse;
use Santosdave\Sabre\Exceptions\SabreApiException;
use Illuminate\Support\Facades\Log;

class OrderSplitHelper
{
    private OrderManagementService $orderService;

    public function __construct(OrderManagementService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function processSplitByPassenger(
        string $orderId,
        array $passengerGroups,
        array $agencyInfo
    ): array {
        try {
            // Validate if split is possible
            if (!$this->validateSplitConfiguration($orderId, 'BY_PASSENGER', $passengerGroups)) {
                throw new SabreApiException('Invalid split configuration for passengers');
            }

            $splitResults = [];
            foreach ($passengerGroups as $group) {
                $request = $this->createSplitRequest($orderId, $group, $agencyInfo);
                $response = $this->orderService->splitOrder($request);

                if ($response->isSuccess()) {
                    $splitResults[] = [
                        'groupId' => $group['id'],
                        'newOrders' => $response->getNewOrders(),
                        'status' => $response->getSplitStatus()
                    ];
                }
            }

            // Log successful split
            Log::info('Order split by passenger completed', [
                'orderId' => $orderId,
                'groupCount' => count($passengerGroups),
                'resultCount' => count($splitResults)
            ]);

            return $splitResults;
        } catch (\Exception $e) {
            Log::error('Split by passenger failed', [
                'orderId' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function processSplitBySegment(
        string $orderId,
        array $segmentGroups,
        array $agencyInfo
    ): array {
        try {
            if (!$this->validateSplitConfiguration($orderId, 'BY_SEGMENT', $segmentGroups)) {
                throw new SabreApiException('Invalid split configuration for segments');
            }

            $splitResults = [];
            foreach ($segmentGroups as $group) {
                $request = new OrderSplitRequest($orderId);

                foreach ($group['segments'] as $segment) {
                    $request->addSplitItem(
                        $segment['itemId'],
                        $segment['passengerIds']
                    );
                }

                $this->setAgencyInfo($request, $agencyInfo);
                $response = $this->orderService->splitOrder($request);

                if ($response->isSuccess()) {
                    $splitResults[] = $this->processSplitResponse($response, $group);
                }
            }

            return $splitResults;
        } catch (\Exception $e) {
            Log::error('Split by segment failed', [
                'orderId' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function handleSplitWithAncillaries(
        string $orderId,
        array $splitConfig,
        array $ancillaryMapping,
        array $agencyInfo
    ): array {
        try {
            // Get available ancillaries before split
            $options = $this->orderService->getSplitOptions($orderId);

            // Validate ancillary distribution
            $this->validateAncillaryMapping($ancillaryMapping, $options['ancillaries'] ?? []);

            // Perform the split
            $request = new OrderSplitRequest($orderId);

            foreach ($splitConfig['items'] as $item) {
                $request->addSplitItem(
                    $item['itemId'],
                    $item['passengerIds']
                );
            }

            // Add ancillary mapping
            $request->setAncillaryMapping($ancillaryMapping);
            $this->setAgencyInfo($request, $agencyInfo);

            $response = $this->orderService->splitOrder($request);
            return $this->processSplitWithAncillariesResponse($response);
        } catch (\Exception $e) {
            Log::error('Split with ancillaries failed', [
                'orderId' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function validateSplitConfiguration(string $orderId, string $splitType, array $groups): bool
    {
        try {
            $options = $this->orderService->getSplitOptions($orderId);

            // Check if split type is supported
            if (!in_array($splitType, $options['supportedSplitTypes'] ?? [])) {
                return false;
            }

            // Validate groups based on split type
            foreach ($groups as $group) {
                if (!$this->validateGroup($group, $splitType, $options)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::warning('Split validation failed', [
                'orderId' => $orderId,
                'splitType' => $splitType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function validateGroup(array $group, string $splitType, array $options): bool
    {
        switch ($splitType) {
            case 'BY_PASSENGER':
                return $this->validatePassengerGroup($group, $options);
            case 'BY_SEGMENT':
                return $this->validateSegmentGroup($group, $options);
            default:
                return false;
        }
    }

    private function validatePassengerGroup(array $group, array $options): bool
    {
        // Validate passenger IDs exist in original order
        $validPassengerIds = $options['passengers'] ?? [];
        foreach ($group['passengerIds'] as $passengerId) {
            if (!in_array($passengerId, $validPassengerIds)) {
                return false;
            }
        }
        return true;
    }

    private function validateSegmentGroup(array $group, array $options): bool
    {
        // Validate segment IDs exist in original order
        $validSegmentIds = $options['segments'] ?? [];
        foreach ($group['segments'] as $segment) {
            if (!in_array($segment['itemId'], $validSegmentIds)) {
                return false;
            }
        }
        return true;
    }

    private function validateAncillaryMapping(array $mapping, array $availableAncillaries): bool
    {
        foreach ($mapping as $ancillaryId => $distribution) {
            if (!isset($availableAncillaries[$ancillaryId])) {
                throw new SabreApiException("Invalid ancillary ID: {$ancillaryId}");
            }
        }
        return true;
    }

    private function createSplitRequest(string $orderId, array $group, array $agencyInfo): OrderSplitRequest
    {
        $request = new OrderSplitRequest($orderId);

        foreach ($group['items'] as $itemId) {
            $request->addSplitItem($itemId, $group['passengerIds']);
        }

        $this->setAgencyInfo($request, $agencyInfo);
        return $request;
    }

    private function setAgencyInfo(OrderSplitRequest $request, array $agencyInfo): void
    {
        $request->setTravelAgencyParty(
            $agencyInfo['iataNumber'],
            $agencyInfo['pseudoCityCode'],
            $agencyInfo['agencyId'],
            $agencyInfo['name']
        );
    }

    private function processSplitResponse(OrderSplitResponse $response, array $group): array
    {
        return [
            'groupId' => $group['id'] ?? null,
            'newOrders' => $response->getNewOrders(),
            'status' => $response->getSplitStatus(),
            'originalOrderId' => $response->getOriginalOrderId()
        ];
    }

    private function processSplitWithAncillariesResponse(OrderSplitResponse $response): array
    {
        $result = $this->processSplitResponse($response, []);

        // Add ancillary distribution details
        if ($response->isSuccess()) {
            foreach ($response->getNewOrders() as $order) {
                $result['ancillaryDistribution'][$order['orderId']] =
                    $this->extractAncillaryDetails($order);
            }
        }

        return $result;
    }

    private function extractAncillaryDetails(array $order): array
    {
        $ancillaries = [];
        foreach ($order['orderItems'] ?? [] as $item) {
            if ($item['serviceDetails']['type'] === 'ancillary') {
                $ancillaries[] = [
                    'id' => $item['id'],
                    'serviceCode' => $item['serviceDetails']['code'],
                    'passengerRefs' => $item['passengerRefs'],
                    'status' => $item['status']
                ];
            }
        }
        return $ancillaries;
    }
}