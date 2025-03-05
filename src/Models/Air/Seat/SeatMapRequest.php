<?php

namespace Santosdave\SabreWrapper\Models\Air\Seat;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class SeatMapRequest implements SabreRequest
{
    private string $requestType = 'offerId';
    private string $source = 'NDC';

    // Common parameters
    private ?string $agentDutyCode = '*';
    private ?string $countryCode = 'US';
    private ?string $cityCode = 'SFO';

    // Request-specific parameters
    private ?string $offerId = null;
    private ?string $orderId = null;
    private ?string $pnrLocator = null;
    private array $segmentRefIds = [];
    private array $passengers = [];
    private array $fareComponents = [];
    private ?string $currency = null;
    private array $originDestinationSegments = [];

    public function setRequestType(string $type): self
    {
        $this->requestType = $type;
        return $this;
    }

    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    // Setters for point of sale
    public function setAgentDutyCode(?string $code): self
    {
        $this->agentDutyCode = $code;
        return $this;
    }

    public function getAgentDutyCode(): ?string
    {
        return $this->agentDutyCode;
    }

    public function setCountryCode(?string $code): self
    {
        $this->countryCode = $code;
        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCityCode(?string $code): self
    {
        $this->cityCode = $code;
        return $this;
    }

    public function getCityCode(): ?string
    {
        return $this->cityCode;
    }

    // Specific request type setters
    public function setOfferId(string $offerId): self
    {
        $this->offerId = $offerId;
        $this->requestType = 'offerId';
        return $this;
    }

    public function getOfferId(): ?string
    {
        return $this->offerId;
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

    public function setPnrLocator(string $pnrLocator): self
    {
        $this->pnrLocator = $pnrLocator;
        $this->requestType = 'stateless';
        return $this;
    }

    public function getPnrLocator(): ?string
    {
        return $this->pnrLocator;
    }

    public function addSegmentRefId(string $segmentRefId): self
    {
        $this->segmentRefIds[] = $segmentRefId;
        $this->requestType = 'payload';
        return $this;
    }

    public function getSegmentRefIds(): array
    {
        return $this->segmentRefIds;
    }

    public function addPassenger(array $passenger): self
    {
        $this->passengers[] = $passenger;
        return $this;
    }

    public function getPassengers(): array
    {
        return $this->passengers;
    }

    public function addFareComponent(array $fareComponent): self
    {
        $this->fareComponents[] = $fareComponent;
        return $this;
    }

    public function getFareComponents(): array
    {
        return $this->fareComponents;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function addOriginDestinationSegment(array $segment): self
    {
        $this->originDestinationSegments[] = $segment;
        return $this;
    }

    public function getOriginDestinationSegments(): array
    {
        return $this->originDestinationSegments;
    }

    public function validate(): bool
    {
        switch ($this->requestType) {
            case 'offerId':
                if (empty($this->offerId)) {
                    throw new SabreApiException('Offer ID is required for offerId request type');
                }
                break;
            case 'orderId':
                if (empty($this->orderId)) {
                    throw new SabreApiException('Order ID is required for orderId request type');
                }
                break;
            case 'payload':
                if (empty($this->segmentRefIds)) {
                    throw new SabreApiException('Segment Reference IDs are required for payload request type');
                }
                if (empty($this->passengers)) {
                    throw new SabreApiException('Passengers are required for payload request type');
                }
                break;
            case 'stateless':
                if (empty($this->pnrLocator)) {
                    throw new SabreApiException('PNR Locator is required for stateless request type');
                }
                break;
            default:
                throw new SabreApiException("Invalid request type: {$this->requestType}");
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        // The actual conversion will be handled in the SeatService
        // This method is just to satisfy the SabreRequest interface
        return [];
    }
}
