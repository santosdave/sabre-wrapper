<?php

namespace Santosdave\Sabre\Models\Air\Exchange;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class RefundQuoteRequest implements SabreRequest
{
    private $ticketNumber;
    private $passengerName;
    private $refundAmount;

    public function __construct($ticketNumber, $passengerName, $refundAmount)
    {
        $this->ticketNumber = $ticketNumber;
        $this->passengerName = $passengerName;
        $this->refundAmount = $refundAmount;
    }

    public function getTicketNumber()
    {
        return $this->ticketNumber;
    }

    public function setTicketNumber($ticketNumber)
    {
        $this->ticketNumber = $ticketNumber;
    }

    public function getPassengerName()
    {
        return $this->passengerName;
    }

    public function setPassengerName($passengerName)
    {
        $this->passengerName = $passengerName;
    }

    public function getRefundAmount()
    {
        return $this->refundAmount;
    }

    public function setRefundAmount($refundAmount)
    {
        $this->refundAmount = $refundAmount;
    }

    public function toArray(): array
    {
        return [];
    }

    public function validate(): bool
    {
        return !empty($this->ticketNumber) && !empty($this->passengerName) && !empty($this->refundAmount);
    }
}