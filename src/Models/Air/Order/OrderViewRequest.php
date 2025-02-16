<?php

namespace Santosdave\SabreWrapper\Models\Air\Order;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class OrderViewRequest implements SabreRequest
{
    private string $orderId;
    private ?string $requestType = null;
    private ?bool $displayPaymentCardNumbers = null;
    private ?bool $reshop = null;
    private ?bool $checkState = null;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    // Add methods for optional parameters
    public function setRequestType(string $type): self
    {
        $this->requestType = $type;
        return $this;
    }

    public function setDisplayPaymentCardNumbers(bool $display): self
    {
        $this->displayPaymentCardNumbers = $display;
        return $this;
    }

    public function setReshop(bool $reshop): self
    {
        $this->reshop = $reshop;
        return $this;
    }

    public function setCheckState(bool $checkState): self
    {
        $this->checkState = $checkState;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->orderId)) {
            throw new SabreApiException('Order ID is required');
        }
        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'id' => $this->orderId
        ];

        // Add optional fields
        if ($this->requestType) {
            $request['requestType'] = $this->requestType;
        }

        if ($this->displayPaymentCardNumbers !== null) {
            $request['displayPaymentCardNumbers'] = $this->displayPaymentCardNumbers;
        }

        if ($this->reshop !== null) {
            $request['reshop'] = $this->reshop;
        }

        if ($this->checkState !== null) {
            $request['checkState'] = $this->checkState;
        }

        return $request;
    }
}