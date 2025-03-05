<?php

namespace Santosdave\SabreWrapper\Contracts\Services;

use Santosdave\SabreWrapper\Models\Air\Ancillary\AncillaryRequest;
use Santosdave\SabreWrapper\Models\Air\Ancillary\AncillaryResponse;

interface AncillaryServiceInterface
{
    /**
     * Get ancillaries for a specific order
     * 
     * @param AncillaryRequest $request Ancillary request details
     * @return AncillaryResponse Ancillary response with service details
     */
    public function getAncillaries(AncillaryRequest $request): AncillaryResponse;

    /**
     * Get pre-bookable ancillaries for an order
     * 
     * @param string $orderId Unique order identifier
     * @return AncillaryResponse Ancillary response with pre-bookable services
     */
    public function getPrebookableAncillaries(string $orderId): AncillaryResponse;

    /**
     * Get post-bookable ancillaries for an order
     * 
     * @param string $orderId Unique order identifier
     * @param array|null $segments Optional segment references
     * @return AncillaryResponse Ancillary response with post-bookable services
     */
    public function getPostbookableAncillaries(
        string $orderId,
        ?array $segments = null
    ): AncillaryResponse;

    /**
     * Add ancillary to an order
     * 
     * @param string $orderId Unique order identifier
     * @param string $serviceId Service identifier
     * @param array $passengers Passenger references
     * @param array|null $paymentInfo Optional payment information
     * @return AncillaryResponse Ancillary response after adding service
     */
    public function addAncillaryToOrder(
        string $orderId,
        string $serviceId,
        array $passengers,
        ?array $paymentInfo = null
    ): AncillaryResponse;

    /**
     * Remove ancillary from an order
     * 
     * @param string $orderId Unique order identifier
     * @param string $serviceId Service identifier
     * @return AncillaryResponse Ancillary response after removing service
     */
    public function removeAncillaryFromOrder(
        string $orderId,
        string $serviceId
    ): AncillaryResponse;

    /**
     * Get ancillary service rules
     * 
     * @param string $serviceCode Service code
     * @param string $carrierCode Carrier code
     * @return AncillaryResponse Ancillary response with service rules
     */
    public function getAncillaryRules(
        string $serviceCode,
        string $carrierCode
    ): AncillaryResponse;
}
