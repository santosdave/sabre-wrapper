<?php

namespace Santosdave\Sabre\Models\Air\Seat;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class SeatAssignRequest implements SabreRequest
{
    private string $orderId;
    private array $seatAssignments = [];
    private ?array $paymentInfo = null;
    private ?array $party = null;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function addSeatAssignment(
        string $passengerId,
        string $segmentId,
        string $seatNumber,
        ?array $preferences = null
    ): self {
        $this->seatAssignments[] = array_filter([
            'passengerId' => $passengerId,
            'segmentId' => $segmentId,
            'seatNumber' => $seatNumber,
            'preferences' => $preferences
        ]);
        return $this;
    }

    public function setPaymentInfo(array $paymentInfo): self
    {
        $this->paymentInfo = $paymentInfo;
        return $this;
    }

    public function setPaymentCard(
        string $cardNumber,
        string $expirationDate,
        string $cardCode,
        string $cardType,
        float $amount,
        string $currency
    ): self {
        $this->paymentInfo = [
            'amount' => [
                'amount' => $amount,
                'currency' => $currency
            ],
            'method' => [
                'card' => [
                    'number' => $cardNumber,
                    'expirationDate' => $expirationDate,
                    'securityCode' => $cardCode,
                    'type' => $cardType
                ]
            ]
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

        if (empty($this->seatAssignments)) {
            throw new SabreApiException('At least one seat assignment is required');
        }

        foreach ($this->seatAssignments as $assignment) {
            if (empty($assignment['passengerId'])) {
                throw new SabreApiException('Passenger ID is required for seat assignment');
            }
            if (empty($assignment['segmentId'])) {
                throw new SabreApiException('Segment ID is required for seat assignment');
            }
            if (empty($assignment['seatNumber'])) {
                throw new SabreApiException('Seat number is required for seat assignment');
            }
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'orderId' => $this->orderId,
            'seatAssignments' => $this->seatAssignments
        ];

        if ($this->paymentInfo) {
            $request['paymentInfo'] = $this->paymentInfo;
        }

        if ($this->party) {
            $request['party'] = $this->party;
        }

        return $request;
    }
}