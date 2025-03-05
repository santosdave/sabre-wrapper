<?php

namespace Santosdave\SabreWrapper\Models\Air\Ancillary;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class AncillaryRequest implements SabreRequest
{
    private string $requestType = 'orderId';
    private ?string $orderId = null;
    private array $requestedSegmentRefs = [];
    private array $requestedPaxRefs = [];
    private ?string $groupCode = null;

    // Point of Sale information
    private ?string $agentDutyCode = '*';
    private ?string $countryCode = 'US';
    private ?string $cityCode = 'SFO';

    public function setRequestType(string $type): self
    {
        $this->requestType = $type;
        return $this;
    }

    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function setOrderId(string $orderId): self
    {
        $this->orderId = $orderId;
        $this->requestType = 'orderId';
        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function addRequestedSegmentRef(string $segmentRef): self
    {
        $this->requestedSegmentRefs[] = $segmentRef;
        return $this;
    }

    public function getRequestedSegmentRefs(): array
    {
        return $this->requestedSegmentRefs;
    }

    public function addRequestedPaxRef(string $paxRef): self
    {
        $this->requestedPaxRefs[] = $paxRef;
        return $this;
    }

    public function getRequestedPaxRefs(): array
    {
        return $this->requestedPaxRefs;
    }

    public function setGroupCode(?string $groupCode): self
    {
        $this->groupCode = $groupCode;
        return $this;
    }

    public function getGroupCode(): ?string
    {
        return $this->groupCode;
    }

    // Point of Sale Setters
    public function setAgentDutyCode(?string $code): self
    {
        $this->agentDutyCode = $code;
        return $this;
    }

    public function setCountryCode(?string $code): self
    {
        $this->countryCode = $code;
        return $this;
    }

    public function setCityCode(?string $code): self
    {
        $this->cityCode = $code;
        return $this;
    }

    public function validate(): bool
    {
        if ($this->requestType === 'orderId' && empty($this->orderId)) {
            throw new SabreApiException('Order ID is required for orderId request type');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'requestType' => $this->requestType,
            'request' => [
                // Basic order request
                'orderId' => $this->orderId
            ]
        ];

        // Optional segment references
        if (!empty($this->requestedSegmentRefs)) {
            $request['request']['requestedSegmentRefs'] = $this->requestedSegmentRefs;
        }

        // Optional passenger references
        if (!empty($this->requestedPaxRefs)) {
            $request['request']['requestedPaxRefs'] = $this->requestedPaxRefs;
        }

        // Optional group code
        if ($this->groupCode) {
            $request['request']['groupCode'] = $this->groupCode;
        }

        return $request;
    }
}
