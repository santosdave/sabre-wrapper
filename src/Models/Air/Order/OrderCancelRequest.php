<?php

namespace Santosdave\Sabre\Models\Air\Order;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class OrderCancelRequest implements SabreRequest
{
    private string $confirmationId;
    private bool $retrieveBooking = true;
    private bool $cancelAll = true;
    private ?array $selectedItems = null;
    private ?array $refundInfo = null;
    private ?array $party = null;

    public function __construct(string $confirmationId)
    {
        $this->confirmationId = $confirmationId;
    }

    public function setRetrieveBooking(bool $retrieve): self
    {
        $this->retrieveBooking = $retrieve;
        return $this;
    }

    public function setCancelAll(bool $cancelAll): self
    {
        $this->cancelAll = $cancelAll;
        return $this;
    }

    public function setSelectedItems(array $items): self
    {
        $this->selectedItems = $items;
        return $this;
    }

    public function setRefundInfo(string $type, array $details): self
    {
        $this->refundInfo = [
            'type' => $type,
            'details' => $details
        ];
        return $this;
    }

    public function setTravelAgencyParty(
        string $iataNumber,
        string $pseudoCityCode,
        string $agencyId,
        string $name
    ): self {
        $this->party = [
            'sender' => [
                'travelAgency' => [
                    'iataNumber' => $iataNumber,
                    'pseudoCityCode' => $pseudoCityCode,
                    'agencyId' => $agencyId,
                    'name' => $name
                ]
            ]
        ];
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->confirmationId)) {
            throw new SabreApiException('Confirmation ID is required');
        }

        if (!$this->cancelAll && empty($this->selectedItems)) {
            throw new SabreApiException('Selected items are required when not cancelling all');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'confirmationId' => $this->confirmationId,
            'retrieveBooking' => $this->retrieveBooking,
            'cancelAll' => $this->cancelAll
        ];

        if (!$this->cancelAll && $this->selectedItems) {
            $request['selectedItems'] = $this->selectedItems;
        }

        if ($this->refundInfo) {
            $request['refundInfo'] = $this->refundInfo;
        }

        if ($this->party) {
            $request['party'] = $this->party;
        }

        return $request;
    }
}