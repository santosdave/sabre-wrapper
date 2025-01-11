<?php

namespace Santosdave\Sabre\Models\Air\Order;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class OrderSplitRequest implements SabreRequest
{
    private string $orderId;
    private array $splitItems = [];
    private ?array $party = null;
    private array $passengers = [];
    private bool $maintainContacts = true;

    private $ancillaryMapping = [];

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function addSplitItem(string $itemId, array $passengerIds): self
    {
        $this->splitItems[] = [
            'orderItemId' => $itemId,
            'passengerIds' => $passengerIds
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

    public function addPassenger(
        string $id,
        string $type,
        string $givenName,
        string $surname,
        ?string $dateOfBirth = null
    ): self {
        $this->passengers[] = array_filter([
            'id' => $id,
            'type' => $type,
            'givenName' => $givenName,
            'surname' => $surname,
            'dateOfBirth' => $dateOfBirth
        ]);
        return $this;
    }

    public function setMaintainContacts(bool $maintain): self
    {
        $this->maintainContacts = $maintain;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->orderId)) {
            throw new SabreApiException('Order ID is required');
        }

        if (empty($this->splitItems)) {
            throw new SabreApiException('At least one split item is required');
        }

        foreach ($this->splitItems as $item) {
            if (empty($item['orderItemId']) || empty($item['passengerIds'])) {
                throw new SabreApiException('Each split item must have an order item ID and passenger IDs');
            }
        }

        return true;
    }

    public function setAncillaryMapping(array $mapping): self

    {

        $this->ancillaryMapping = $mapping;

        return $this;
    }



    public function getAncillaryMapping(): array

    {

        return $this->ancillaryMapping;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'id' => $this->orderId,
            'splitItems' => $this->splitItems,
            'maintainContacts' => $this->maintainContacts
        ];

        if ($this->party) {
            $request['party'] = $this->party;
        }

        if (!empty($this->passengers)) {
            $request['passengers'] = $this->passengers;
        }

        return $request;
    }
}