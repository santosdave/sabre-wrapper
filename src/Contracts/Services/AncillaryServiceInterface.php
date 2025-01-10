<?php

namespace Santosdave\Sabre\Contracts\Services;

use Santosdave\Sabre\Models\Air\Ancillary\AncillaryRequest;
use Santosdave\Sabre\Models\Air\Ancillary\AncillaryResponse;

interface AncillaryServiceInterface
{
    public function getAncillaries(AncillaryRequest $request): AncillaryResponse;

    public function getPrebookableAncillaries(string $orderId): AncillaryResponse;

    public function getPostbookableAncillaries(
        string $orderId,
        ?array $segments = null
    ): AncillaryResponse;

    public function addAncillaryToOrder(
        string $orderId,
        string $serviceId,
        array $passengers,
        ?array $paymentInfo = null
    ): AncillaryResponse;

    public function removeAncillaryFromOrder(
        string $orderId,
        string $serviceId
    ): AncillaryResponse;

    public function getAncillaryRules(
        string $serviceCode,
        string $carrierCode
    ): AncillaryResponse;
}