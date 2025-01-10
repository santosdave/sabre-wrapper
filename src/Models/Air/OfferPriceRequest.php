<?php

namespace Santosdave\Sabre\Models\Air;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class OfferPriceRequest implements SabreRequest
{
    private array $offerItems = [];
    private ?array $formOfPayment = null;
    private ?string $currency = null;
    private array $passengers = [];

    public function addOfferItem(string $offerItemId): self
    {
        $this->offerItems[] = $offerItemId;
        return $this;
    }

    public function setFormOfPayment(string $type, array $details): self
    {
        $this->formOfPayment = [
            'type' => $type,
            'details' => $details
        ];
        return $this;
    }

    public function setCreditCard(
        string $cardType,
        string $binNumber,
        string $subCode = null
    ): self {
        $this->formOfPayment = [
            'paymentCard' => array_filter([
                'cardType' => $cardType,
                'binNumber' => $binNumber,
                'subCode' => $subCode
            ])
        ];
        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function addPassenger(string $passengerId, string $passengerType): self
    {
        $this->passengers[] = [
            'id' => $passengerId,
            'type' => $passengerType
        ];
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->offerItems)) {
            throw new SabreApiException('At least one offer item ID is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'query' => [
                [
                    'offerItemId' => $this->offerItems
                ]
            ]
        ];

        if ($this->formOfPayment) {
            $request['params']['formOfPayment'] = [$this->formOfPayment];
        }

        if ($this->currency) {
            $request['params']['currency'] = $this->currency;
        }

        if (!empty($this->passengers)) {
            $request['params']['passengers'] = $this->passengers;
        }

        return $request;
    }
}