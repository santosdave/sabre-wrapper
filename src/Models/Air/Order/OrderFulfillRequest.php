<?php

namespace Santosdave\SabreWrapper\Models\Air\Order;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class OrderFulfillRequest implements SabreRequest
{
    private string $orderId;
    private array $paymentInfo;
    private array $orderItemRefs = [];
    private ?array $party = null;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function setPaymentCard(
        string $cardNumber,
        string $expirationDate,
        string $vendorCode,
        string $cvv,
        string $contactInfoRefId
    ): self {
        $this->paymentInfo = [
            'paymentMethod' => [
                'paymentCard' => [
                    'cardNumber' => $cardNumber,
                    'expirationDate' => $expirationDate,
                    'vendorCode' => $vendorCode,
                    'cvv' => $cvv,
                    'contactInfoRefId' => $contactInfoRefId
                ]
            ]
        ];
        return $this;
    }

    public function setAmount(float $amount, string $currency): self
    {
        $this->paymentInfo['amount'] = [
            'amount' => $amount,
            'code' => $currency
        ];
        return $this;
    }

    public function addOrderItemRef(string $itemId): self
    {
        $this->orderItemRefs[] = $itemId;
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

        if (empty($this->paymentInfo)) {
            throw new SabreApiException('Payment information is required');
        }

        if (empty($this->orderItemRefs)) {
            throw new SabreApiException('At least one order item reference is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'id' => $this->orderId,
            'actions' => [
                [
                    'fulfillOrder' => [
                        'paymentInfo' => array_merge(
                            $this->paymentInfo,
                            ['orderItemRefIds' => $this->orderItemRefs]
                        )
                    ]
                ]
            ]
        ];

        if ($this->party) {
            $request['party'] = $this->party;
        }

        return $request;
    }
}
