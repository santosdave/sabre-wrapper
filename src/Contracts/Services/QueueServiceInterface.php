<?php

namespace Santosdave\Sabre\Contracts\Services;

use Santosdave\Sabre\Models\Queue\QueueListRequest;
use Santosdave\Sabre\Models\Queue\QueueListResponse;
use Santosdave\Sabre\Models\Queue\QueuePlaceRequest;
use Santosdave\Sabre\Models\Queue\QueueRemoveRequest;

interface QueueServiceInterface
{
    public function listQueue(QueueListRequest $request): QueueListResponse;
    public function placeOnQueue(QueuePlaceRequest $request): bool;
    public function removeFromQueue(QueueRemoveRequest $request): bool;
    public function getPnrFromQueue(string $queueNumber, int $recordLocator): array;
    public function moveQueue(string $sourceQueue, string $targetQueue, ?array $criteria = null): bool;
}
