<?php

namespace Santosdave\SabreWrapper\Contracts\Services;

use Santosdave\SabreWrapper\Models\Queue\QueueListRequest;
use Santosdave\SabreWrapper\Models\Queue\QueueListResponse;
use Santosdave\SabreWrapper\Models\Queue\QueuePlaceRequest;
use Santosdave\SabreWrapper\Models\Queue\QueueRemoveRequest;

interface QueueServiceInterface
{
    public function listQueue(QueueListRequest $request): QueueListResponse;
    public function placeOnQueue(QueuePlaceRequest $request): bool;
    public function removeFromQueue(QueueRemoveRequest $request): bool;
    public function getPnrFromQueue(string $queueNumber, int $recordLocator): array;
    public function moveQueue(string $sourceQueue, string $targetQueue, ?array $criteria = null): bool;
}
