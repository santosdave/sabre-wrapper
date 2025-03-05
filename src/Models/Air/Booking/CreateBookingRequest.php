<?php

namespace Santosdave\SabreWrapper\Models\Air\Booking;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class CreateBookingRequest implements SabreRequest
{
    private ?int $asynchronousUpdateWaitTime = null;
    private ?array $flightOffer = null;
    private ?array $agency = null;
    private array $travelers = [];
    private ?array $contactInfo = null;
    private ?array $flightDetails = null;
    private ?array $payment = null;
    private ?array $remarks = null;

    /**
     * Set asynchronous update wait time
     * 
     * @param int $waitTime Wait time in milliseconds
     * @return self
     */
    public function setAsynchronousUpdateWaitTime(int $waitTime): self
    {
        $this->asynchronousUpdateWaitTime = $waitTime;
        return $this;
    }

    /**
     * Set NDC flight offer details
     * 
     * @param array $offer Flight offer details
     * @return self
     */
    public function setFlightOffer(array $offer): self
    {
        $this->flightOffer = $offer;
        return $this;
    }

    /**
     * Check if the booking has an NDC offer
     * 
     * @return bool
     */
    public function hasNdcOffer(): bool
    {
        return !empty($this->flightOffer)
            && isset($this->flightOffer['offerId'])
            && isset($this->flightOffer['selectedOfferItems']);
    }

    /**
     * Set agency information
     * 
     * @param array $agency Agency details
     * @return self
     */
    public function setAgency(array $agency): self
    {
        $this->agency = $agency;
        return $this;
    }

    /**
     * Add a traveler to the booking
     * 
     * @param array $traveler Traveler details
     * @return self
     */
    public function addTraveler(array $traveler): self
    {
        // Validate and sanitize traveler data
        $sanitizedTraveler = $this->sanitizeTravelerData($traveler);
        $this->travelers[] = $sanitizedTraveler;
        return $this;
    }

    /**
     * Set contact information
     * 
     * @param array $contactInfo Contact details
     * @return self
     */
    public function setContactInfo(array $contactInfo): self
    {
        $this->contactInfo = $this->sanitizeContactInfo($contactInfo);
        return $this;
    }

    /**
     * Set flight details for traditional booking
     * 
     * @param array $flightDetails Flight details
     * @return self
     */
    public function setFlightDetails(array $flightDetails): self
    {
        $this->flightDetails = $this->sanitizeFlightDetails($flightDetails);
        return $this;
    }

    /**
     * Set payment information
     * 
     * @param array $payment Payment details
     * @return self
     */
    public function setPayment(array $payment): self
    {
        $this->payment = $this->sanitizePaymentData($payment);
        return $this;
    }

    /**
     * Add remarks to the booking
     * 
     * @param array $remarks Booking remarks
     * @return self
     */
    public function addRemarks(array $remarks): self
    {
        $this->remarks = $remarks;
        return $this;
    }

    /**
     * Validate the booking request
     * 
     * @return bool
     * @throws SabreApiException
     */
    public function validate(): bool
    {
        // Validate based on booking type
        if ($this->hasNdcOffer()) {
            return $this->validateNdcBooking();
        }

        return $this->validateTraditionalBooking();
    }

    /**
     * Validate NDC booking
     * 
     * @return bool
     * @throws SabreApiException
     */
    private function validateNdcBooking(): bool
    {
        // Validate flight offer
        if (empty($this->flightOffer['offerId']) || empty($this->flightOffer['selectedOfferItems'])) {
            throw new SabreApiException('Invalid NDC flight offer: Missing offerId or selectedOfferItems');
        }

        // Validate travelers
        if (empty($this->travelers)) {
            throw new SabreApiException('At least one traveler is required');
        }

        // Validate contact info
        if (empty($this->contactInfo)) {
            throw new SabreApiException('Contact information is required');
        }

        return true;
    }

    /**
     * Validate traditional booking
     * 
     * @return bool
     * @throws SabreApiException
     */
    private function validateTraditionalBooking(): bool
    {
        // Validate flight details
        if (empty($this->flightDetails) || empty($this->flightDetails['flights'])) {
            throw new SabreApiException('Flight details are required for traditional booking');
        }

        // Validate travelers
        if (empty($this->travelers)) {
            throw new SabreApiException('At least one traveler is required');
        }

        // Validate agency information
        if (empty($this->agency)) {
            throw new SabreApiException('Agency information is required for traditional booking');
        }

        return true;
    }

    /**
     * Convert request to array for API submission
     * 
     * @return array
     */
    public function toArray(): array
    {
        // Validate before converting
        $this->validate();

        // Prepare payload
        $payload = array_filter([
            'asynchronousUpdateWaitTime' => $this->asynchronousUpdateWaitTime,
            'flightOffer' => $this->flightOffer,
            'agency' => $this->agency,
            'travelers' => $this->travelers,
            'contactInfo' => $this->contactInfo,
            'flightDetails' => $this->flightDetails,
            'payment' => $this->payment,
            'remarks' => $this->remarks
        ]);

        return $payload;
    }

    /**
     * Sanitize traveler data
     * 
     * @param array $traveler Raw traveler data
     * @return array Sanitized traveler data
     */
    private function sanitizeTravelerData(array $traveler): array
    {
        $sanitized = [
            'givenName' => $traveler['givenName'] ?? null,
            'surname' => $traveler['surname'] ?? null,
            'passengerCode' => $traveler['passengerCode'] ?? 'ADT',
            'birthDate' => $traveler['birthDate'] ?? null
        ];

        // Optional fields
        if (isset($traveler['id'])) {
            $sanitized['id'] = $traveler['id'];
        }

        if (isset($traveler['customerNumber'])) {
            $sanitized['customerNumber'] = $traveler['customerNumber'];
        }

        // Handle identity documents
        if (isset($traveler['identityDocuments'])) {
            $sanitized['identityDocuments'] = $traveler['identityDocuments'];
        }

        return array_filter($sanitized);
    }

    /**
     * Sanitize contact information
     * 
     * @param array $contactInfo Raw contact info
     * @return array Sanitized contact info
     */
    private function sanitizeContactInfo(array $contactInfo): array
    {
        return [
            'emails' => $contactInfo['emails'] ?? [],
            'phones' => $contactInfo['phones'] ?? []
        ];
    }

    /**
     * Sanitize flight details
     * 
     * @param array $flightDetails Raw flight details
     * @return array Sanitized flight details
     */
    private function sanitizeFlightDetails(array $flightDetails): array
    {
        $sanitized = [];

        // Sanitize flights
        if (isset($flightDetails['flights'])) {
            $sanitized['flights'] = array_map(function ($flight) {
                return array_filter([
                    'flightNumber' => $flight['flightNumber'] ?? null,
                    'airlineCode' => $flight['airlineCode'] ?? null,
                    'fromAirportCode' => $flight['fromAirportCode'] ?? null,
                    'toAirportCode' => $flight['toAirportCode'] ?? null,
                    'departureDate' => $flight['departureDate'] ?? null,
                    'departureTime' => $flight['departureTime'] ?? null,
                    'bookingClass' => $flight['bookingClass'] ?? 'Y',
                    'flightStatusCode' => $flight['flightStatusCode'] ?? 'NN',
                    'marriageGroup' => $flight['marriageGroup'] ?? false
                ]);
            }, $flightDetails['flights']);
        }

        // Preserve other flight details
        if (isset($flightDetails['flightPricing'])) {
            $sanitized['flightPricing'] = $flightDetails['flightPricing'];
        }

        return $sanitized;
    }

    /**
     * Sanitize payment data
     * 
     * @param array $payment Raw payment data
     * @return array Sanitized payment data
     */
    private function sanitizePaymentData(array $payment): array
    {
        $sanitized = [];

        // Sanitize billing address
        if (isset($payment['billingAddress'])) {
            $sanitized['billingAddress'] = array_filter([
                'name' => $payment['billingAddress']['name'] ?? null,
                'street' => $payment['billingAddress']['street'] ?? null,
                'city' => $payment['billingAddress']['city'] ?? null,
                'stateProvince' => $payment['billingAddress']['stateProvince'] ?? null,
                'postalCode' => $payment['billingAddress']['postalCode'] ?? null,
                'countryCode' => $payment['billingAddress']['countryCode'] ?? null
            ]);
        }

        // Sanitize forms of payment
        if (isset($payment['formsOfPayment'])) {
            $sanitized['formsOfPayment'] = array_map(function ($paymentMethod) {
                return array_filter([
                    'type' => $paymentMethod['type'] ?? 'PAYMENTCARD',
                    'cardTypeCode' => $paymentMethod['cardTypeCode'] ?? null,
                    'cardNumber' => $paymentMethod['cardNumber'] ?? null,
                    'expiryDate' => $paymentMethod['expiryDate'] ?? null
                ]);
            }, $payment['formsOfPayment']);
        }

        return $sanitized;
    }
}
