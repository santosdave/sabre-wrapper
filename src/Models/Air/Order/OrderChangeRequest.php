<?php

namespace Santosdave\Sabre\Models\Air\Order;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class OrderChangeRequest implements SabreRequest
{
    private string $orderId;
    private array $actions = [];
    private ?array $party = null;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function addFulfillAction(array $paymentInfo): self
    {
        $this->actions[] = [
            'fulfillOrder' => [
                'paymentInfo' => $paymentInfo
            ]
        ];
        return $this;
    }

    public function addCancelAction(array $items = null): self
    {
        $action = ['cancelOrder' => []];
        if ($items) {
            $action['cancelOrder']['orderItems'] = $items;
        }
        $this->actions[] = $action;
        return $this;
    }

    public function addModifyPassengerAction(string $passengerId, array $updates): self
    {
        $this->actions[] = [
            'modifyPassenger' => [
                'passengerId' => $passengerId,
                'updates' => $updates
            ]
        ];
        return $this;
    }

    public function setParty(array $party): self
    {
        $this->party = $party;
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
        if (empty($this->orderId)) {
            throw new SabreApiException('Order ID is required');
        }

        if (empty($this->actions)) {
            throw new SabreApiException('At least one action is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'id' => $this->orderId,
            'actions' => $this->actions
        ];

        if ($this->party) {
            $request['party'] = $this->party;
        }

        return $request;
    }
}