<?php

namespace Santosdave\SabreWrapper\Models\Air\Order;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class OrderCreateRequest implements SabreRequest
{
    private string $offerId;
    private array $selectedOfferItems = [];
    private array $passengers = [];
    private array $contactInfos = [];
    private ?array $customerNumber = null;

    public function setOffer(string $offerId, array $selectedOfferItems): self
    {
        $this->offerId = $offerId;
        $this->selectedOfferItems = array_map(function ($id) {
            return ['id' => $id];
        }, $selectedOfferItems);
        return $this;
    }

    public function addPassenger(
        string $id,
        string $givenName,
        string $surname,
        string $birthdate,
        string $typeCode,
        string $contactInfoRefId
    ): self {
        $this->passengers[] = [
            'id' => $id,
            'givenName' => $givenName,
            'surname' => $surname,
            'birthdate' => $birthdate,
            'typeCode' => $typeCode,
            'contactInfoRefId' => $contactInfoRefId
        ];
        return $this;
    }

    public function addContactInfo(
        string $id,
        ?array $emailAddresses = null,
        ?array $phones = null
    ): self {
        $contactInfo = ['id' => $id];

        if ($emailAddresses) {
            $contactInfo['emailAddresses'] = array_map(function ($email) {
                return ['address' => $email];
            }, $emailAddresses);
        }

        if ($phones) {
            $contactInfo['phones'] = array_map(function ($phone) {
                return ['number' => $phone];
            }, $phones);
        }

        $this->contactInfos[] = $contactInfo;
        return $this;
    }

    public function setCustomerNumber(string $number, string $contactInfoRefId): self
    {
        $this->customerNumber = [
            'number' => $number,
            'contactInfoRefId' => $contactInfoRefId
        ];
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->offerId) || empty($this->selectedOfferItems)) {
            throw new SabreApiException('Offer ID and selected offer items are required');
        }

        if (empty($this->passengers)) {
            throw new SabreApiException('At least one passenger is required');
        }

        if (empty($this->contactInfos)) {
            throw new SabreApiException('At least one contact info is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'createOrders' => [
                [
                    'offerId' => $this->offerId,
                    'selectedOfferItems' => $this->selectedOfferItems
                ]
            ],
            'passengers' => $this->passengers,
            'contactInfos' => $this->contactInfos
        ];

        if ($this->customerNumber) {
            $request['customerNumber'] = $this->customerNumber;
        }

        return $request;
    }
}
