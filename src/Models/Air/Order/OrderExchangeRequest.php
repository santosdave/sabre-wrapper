<?php

namespace Santosdave\Sabre\Models\Air\Order;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class OrderExchangeRequest implements SabreRequest
{
    private string $orderId;
    private array $exchangeItems = [];
    private ?array $newItinerary = null;
    private ?array $paymentInfo = null;
    private ?array $party = null;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function addExchangeItem(string $itemId, array $options = []): self
    {
        $this->exchangeItems[] = array_merge(['id' => $itemId], $options);
        return $this;
    }

    public function setNewItinerary(array $itinerary): self
    {
        $this->newItinerary = $itinerary;
        return $this;
    }

    public function addNewSegment(
        string $origin,
        string $destination,
        string $departureDate,
        string $carrier,
        string $flightNumber,
        ?string $bookingClass = null
    ): self {
        if (!$this->newItinerary) {
            $this->newItinerary = ['segments' => []];
        }

        $this->newItinerary['segments'][] = [
            'departureAirport' => $origin,
            'arrivalAirport' => $destination,
            'departureDate' => $departureDate,
            'marketingCarrier' => [
                'carrier' => $carrier,
                'flightNumber' => $flightNumber
            ],
            'bookingClass' => $bookingClass
        ];

        return $this;
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

    public function setPaymentAmount(float $amount, string $currency): self
    {
        if (!$this->paymentInfo) {
            $this->paymentInfo = [];
        }

        $this->paymentInfo['amount'] = [
            'amount' => $amount,
            'code' => $currency
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
        if (empty($this->orderId)) {
            throw new SabreApiException('Order ID is required');
        }

        if (empty($this->exchangeItems)) {
            throw new SabreApiException('At least one exchange item is required');
        }

        if (empty($this->newItinerary)) {
            throw new SabreApiException('New itinerary details are required');
        }

        if ($this->paymentInfo && !isset($this->paymentInfo['amount'])) {
            throw new SabreApiException('Payment amount is required when providing payment information');
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
                    'exchangeOrder' => [
                        'orderItems' => $this->exchangeItems,
                        'newItinerary' => $this->newItinerary
                    ]
                ]
            ]
        ];

        if ($this->paymentInfo) {
            $request['actions'][0]['exchangeOrder']['paymentInfo'] = $this->paymentInfo;
        }

        if ($this->party) {
            $request['party'] = $this->party;
        }

        return $request;
    }
}