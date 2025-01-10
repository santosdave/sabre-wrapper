<?php

namespace Santosdave\Sabre\Services\Rest;

use Santosdave\Sabre\Services\Base\BaseRestService;
use Santosdave\Sabre\Contracts\Services\QueueServiceInterface;
use Santosdave\Sabre\Models\Queue\QueueListRequest;
use Santosdave\Sabre\Models\Queue\QueueListResponse;
use Santosdave\Sabre\Models\Queue\QueuePlaceRequest;
use Santosdave\Sabre\Models\Queue\QueueRemoveRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class QueueService extends BaseRestService implements QueueServiceInterface
{
    public function listQueue(QueueListRequest $request): QueueListResponse
    {
        try {
            $response = $this->client->post(
                '/v1/queue/list',
                $request->toArray()
            );
            return new QueueListResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to list queue: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function placeOnQueue(QueuePlaceRequest $request): bool
    {
        try {
            $response = $this->client->post(
                '/v1/queue/place',
                $request->toArray()
            );
            return isset($response['status']) && $response['status'] === 'success';
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to place on queue: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function removeFromQueue(QueueRemoveRequest $request): bool
    {
        try {
            $response = $this->client->post(
                '/v1/queue/remove',
                $request->toArray()
            );
            return isset($response['status']) && $response['status'] === 'success';
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to remove from queue: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function getPnrFromQueue(string $queueNumber, int $recordLocator): array
    {
        try {
            return $this->client->get(
                "/v1/queue/{$queueNumber}/record/{$recordLocator}"
            );
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to get PNR from queue: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function moveQueue(string $sourceQueue, string $targetQueue, ?array $criteria = null): bool
    {
        try {
            $data = [
                'sourceQueue' => $sourceQueue,
                'targetQueue' => $targetQueue
            ];

            if ($criteria) {
                $data['criteria'] = $criteria;
            }

            $response = $this->client->post('/v1/queue/move', $data);
            return isset($response['status']) && $response['status'] === 'success';
        } catch (\Exception $e) {
            throw new SabreApiException(
                "Failed to move queue: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
