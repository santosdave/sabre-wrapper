<?php

namespace Santosdave\SabreWrapper\Models\Air\Booking;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class CreateBookingRequest implements SabreRequest
{
    private array $flightOffer;
    private array $travelers = [];
    private array $contactInfo = [];
    private ?string $customerNumber = null;

    public function setFlightOffer(string $offerId, array $selectedOfferItems): self
    {
        $this->flightOffer = [
            'offerId' => $offerId,
            'selectedOfferItems' => $selectedOfferItems
        ];
        return $this;
    }

    public function addTraveler(
        string $id,
        string $givenName,
        string $surname,
        string $birthDate,
        string $passengerCode,
        ?string $customerNumber = null
    ): self {
        $traveler = [
            'id' => $id,
            'givenName' => $givenName,
            'surname' => $surname,
            'birthDate' => $birthDate,
            'passengerCode' => $passengerCode
        ];

        if ($customerNumber) {
            $traveler['customerNumber'] = $customerNumber;
        }

        $this->travelers[] = $traveler;
        return $this;
    }

    public function setContactInfo(
        array $emails,
        array $phones
    ): self {
        $this->contactInfo = [
            'emails' => $emails,
            'phones' => $phones
        ];
        return $this;
    }

    public function setCustomerNumber(string $customerNumber): self
    {
        $this->customerNumber = $customerNumber;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->flightOffer) || empty($this->flightOffer['offerId']) || empty($this->flightOffer['selectedOfferItems'])) {
            throw new SabreApiException('Flight offer and selected offer items are required');
        }

        if (empty($this->travelers)) {
            throw new SabreApiException('At least one traveler is required');
        }

        if (empty($this->contactInfo['emails']) && empty($this->contactInfo['phones'])) {
            throw new SabreApiException('At least one contact method (email or phone) is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'flightOffer' => $this->flightOffer,
            'travelers' => $this->travelers,
            'contactInfo' => $this->contactInfo
        ];

        if ($this->customerNumber) {
            $request['customerNumber'] = $this->customerNumber;
        }

        return $request;
    }
}
