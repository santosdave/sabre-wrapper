<?php

namespace Santosdave\SabreWrapper\Models\Air\Order;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class OrderViewRequest implements SabreRequest
{
    private string $orderId;
    private ?array $filters = null;
    private ?bool $includePayments = null;
    private ?bool $includeDocuments = null;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    public function setIncludePayments(bool $include): self
    {
        $this->includePayments = $include;
        return $this;
    }

    public function setIncludeDocuments(bool $include): self
    {
        $this->includeDocuments = $include;
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

        if ($this->filters) {
            $request['filters'] = $this->filters;
        }

        if ($this->includePayments !== null) {
            $request['includePayments'] = $this->includePayments;
        }

        if ($this->includeDocuments !== null) {
            $request['includeDocuments'] = $this->includeDocuments;
        }

        return $request;
    }
}
