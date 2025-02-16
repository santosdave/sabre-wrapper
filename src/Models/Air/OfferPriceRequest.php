<?php

namespace Santosdave\SabreWrapper\Models\Air;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class OfferPriceRequest implements SabreRequest
{
    private array $offerItems = [];
    private ?array $formOfPayment = null;
    private ?string $currency = null;
    private array $passengers = [];
    private ?array $additionalParams = null;

    /**
     * Add an offer item ID to the pricing request
     *
     * @param string $offerItemId Unique identifier for the offer item
     * @return self
     */
    public function addOfferItem(string $offerItemId): self
    {
        $this->offerItems[] = $offerItemId;
        return $this;
    }

    /**
     * Set form of payment details
     *
     * @param string $type Payment method type
     * @param array $details Additional payment details
     * @return self
     */
    public function setFormOfPayment(string $type, array $details): self
    {
        $this->formOfPayment = [
            'type' => $type,
            'details' => $details
        ];
        return $this;
    }

    /**
     * Set credit card specific payment details
     *
     * @param string $cardType Type of credit card (e.g., 'MC' for Mastercard)
     * @param string $binNumber Bank Identification Number
     * @param string|null $subCode Additional payment sub-code if card type is null then can be CA for cash or CK for cheque
     * @return self
     */
    public function setCreditCard(
        string $cardType = null,
        string $binNumber = null,
        ?string $subCode = null
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

    /**
     * Set the currency for pricing
     *
     * @param string $currency Currency code
     * @return self
     */
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

    /**
     * Add additional custom parameters to the request
     *
     * @param array $params Additional parameters
     * @return self
     */
    public function setAdditionalParams(array $params): self
    {
        $this->additionalParams = $params;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->offerItems)) {
            throw new SabreApiException('At least one offer item ID is required');
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
        $this->validate();

        $request = [
            'query' => [
                [
                    'offerItemId' => $this->offerItems
                ]
            ]
        ];

        // Add parameters section
        $request['params'] = [];

        if ($this->formOfPayment) {
            $request['params']['formOfPayment'] = [$this->formOfPayment];
        }

        if ($this->currency) {
            $request['params']['currency'] = $this->currency;
        }

        if (!empty($this->passengers)) {
            $request['params']['passengers'] = $this->passengers;
        }

        // Add any additional custom parameters
        if ($this->additionalParams) {
            $request['params'] = array_merge(
                $request['params'],
                $this->additionalParams
            );
        }

        return $request;
    }
}