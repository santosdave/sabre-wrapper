<?php

namespace Santosdave\SabreWrapper\Services\Soap;

use Santosdave\SabreWrapper\Services\Base\BaseSoapService;
use Santosdave\SabreWrapper\Contracts\Services\QueueServiceInterface;
use Santosdave\SabreWrapper\Models\Queue\QueueListRequest;
use Santosdave\SabreWrapper\Models\Queue\QueueListResponse;
use Santosdave\SabreWrapper\Models\Queue\QueuePlaceRequest;
use Santosdave\SabreWrapper\Models\Queue\QueueRemoveRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class QueueService extends BaseSoapService implements QueueServiceInterface
{
    public function listQueue(QueueListRequest $request): QueueListResponse
    {
        try {
            $response = $this->client->send(
                'QueueAccessLLSRQ',
                $request->toArray()
            );
            return new QueueListResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to list queue: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function placeOnQueue(QueuePlaceRequest $request): bool
    {
        try {
            $response = $this->client->send(
                'QueuePlaceLLSRQ',
                $request->toArray()
            );
            return isset($response['QueuePlaceRS']['Success']);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to place on queue: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function removeFromQueue(QueueRemoveRequest $request): bool
    {
        try {
            $response = $this->client->send(
                'QueueRemoveLLSRQ',
                $request->toArray()
            );
            return isset($response['QueueRemoveRS']['Success']);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to remove from queue: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getPnrFromQueue(string $queueNumber, int $recordLocator): array
    {
        try {
            $response = $this->client->send('GetReservationLLSRQ', [
                'GetReservationRQ' => [
                    'QueueInfo' => [
                        'QueueNumber' => $queueNumber,
                        'RecordLocator' => $recordLocator
                    ]
                ]
            ]);
            return $response['GetReservationRS'] ?? [];
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to get PNR from queue: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function moveQueue(string $sourceQueue, string $targetQueue, ?array $criteria = null): bool
    {
        try {
            $request = [
                'QueueMoveRQ' => [
                    'SourceQueue' => [
                        'Number' => $sourceQueue
                    ],
                    'TargetQueue' => [
                        'Number' => $targetQueue
                    ]
                ]
            ];

            if ($criteria) {
                $request['QueueMoveRQ']['Criteria'] = $criteria;
            }

            $response = $this->client->send('QueueMoveLLSRQ', $request);
            return isset($response['QueueMoveRS']['Success']);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to move queue: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
