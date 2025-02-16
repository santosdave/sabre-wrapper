<?php

namespace Santosdave\SabreWrapper\Models\Air\Order;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class OrderCreateRequest implements SabreRequest
{
    private ?array $party = null;
    private array $createOrders = [];
    private array $contactInfos = [];
    private array $passengers = [];
    private ?array $customerNumber = null;
    private ?bool $createPriceQuote = null;
    private ?array $transactionOptions = null;
    private ?array $loyaltyProgramAccount = null;
    private ?bool $displayPaymentCardNumbers = null;
    private array $airlineRemarks = [];
    private array $seatAdds = [];

    /**
     * Set travel agency party information
     *
     * @param string $iataNumber IATA number
     * @param string $pseudoCityCode Pseudo City Code
     * @param string $agencyId Agency ID
     * @param string $name Agency Name
     * @param ?string $typeCode Agency Type Code
     * @return self
     */
    public function setTravelAgencyParty(
        string $iataNumber,
        string $pseudoCityCode,
        string $agencyId,
        string $name,
        ?string $typeCode = null
    ): self {
        $this->party = [
            'sender' => [
                'travelAgency' => array_filter([
                    'iataNumber' => $iataNumber,
                    'pseudoCityCode' => $pseudoCityCode,
                    'agencyId' => $agencyId,
                    'name' => $name,
                    'typeCode' => $typeCode
                ])
            ]
        ];
        return $this;
    }


    /**
     * Add an order to be created
     *
     * @param string $offerId Unique identifier of the offer
     * @param array $selectedOfferItems Selected offer items
     * @return self
     */
    public function addCreateOrder(string $offerId, array $selectedOfferItems): self
    {
        $this->createOrders[] = [
            'offerId' => $offerId,
            'selectedOfferItems' => array_map(function ($item) {
                return is_array($item) ? $item : ['id' => $item];
            }, $selectedOfferItems)
        ];
        return $this;
    }


    /**
     * Add contact information
     *
     * @param string $id Unique contact ID
     * @param array $options Additional contact details
     * @return self
     */
    public function addContactInfo(string $id, array $options = []): self
    {
        $contactInfo = ['id' => $id];

        if (isset($options['emailAddresses'])) {
            $contactInfo['emailAddresses'] = array_map(function ($email) {
                return is_string($email)
                    ? ['address' => $email]
                    : $email;
            }, $options['emailAddresses']);
        }

        if (isset($options['phones'])) {
            $contactInfo['phones'] = array_map(function ($phone) {
                return is_string($phone)
                    ? ['number' => $phone]
                    : $phone;
            }, $options['phones']);
        }

        // Add other optional contact info fields
        $optionalFields = [
            'contactRefusedIndicator',
            'givenName',
            'surname',
            'contactType',
            'postalAddresses'
        ];
        foreach ($optionalFields as $field) {
            if (isset($options[$field])) {
                $contactInfo[$field] = $options[$field];
            }
        }

        $this->contactInfos[] = $contactInfo;
        return $this;
    }

    /**
     * Add passenger details
     *
     * @param string $id Passenger ID
     * @param array $passengerDetails Passenger details
     * @return self
     */
    public function addPassenger(string $id, array $passengerDetails): self
    {
        $passenger = ['id' => $id];

        $mappedFields = [
            'externalId' => 'externalId',
            'typeCode' => 'typeCode',
            'citizenshipCountryCode' => 'citizenshipCountryCode',
            'contactInfoRefId' => 'contactInfoRefId',
            'age' => 'age',
            'ageUnitCode' => 'ageUnitCode',
            'birthdate' => 'birthdate',
            'titleName' => 'titleName',
            'givenName' => 'givenName',
            'middleName' => 'middleName',
            'surname' => 'surname',
            'suffixName' => 'suffixName',
            'genderCode' => 'genderCode',
            'passengerReference' => 'passengerReference'
        ];

        foreach ($mappedFields as $key => $field) {
            if (isset($passengerDetails[$key])) {
                $passenger[$field] = $passengerDetails[$key];
            }
        }

        // Handle optional complex fields
        if (isset($passengerDetails['contactInfoRefIds'])) {
            $passenger['contactInfoRefIds'] = $passengerDetails['contactInfoRefIds'];
        }

        if (isset($passengerDetails['remarks'])) {
            $passenger['remarks'] = $passengerDetails['remarks'];
        }

        if (isset($passengerDetails['identityDocuments'])) {
            $passenger['identityDocuments'] = $passengerDetails['identityDocuments'];
        }

        if (isset($passengerDetails['loyaltyProgramAccounts'])) {
            $passenger['loyaltyProgramAccounts'] = $passengerDetails['loyaltyProgramAccounts'];
        }

        if (isset($passengerDetails['employer'])) {
            $passenger['employer'] = $passengerDetails['employer'];
        }

        $this->passengers[] = $passenger;
        return $this;
    }

    /**
     * Set customer number
     *
     * @param string $number Customer number
     * @param ?string $contactInfoRefId Optional contact info reference ID
     * @return self
     */
    public function setCustomerNumber(string $number, ?string $contactInfoRefId = null): self
    {
        $this->customerNumber = array_filter([
            'number' => $number,
            'contactInfoRefId' => $contactInfoRefId
        ]);
        return $this;
    }

    /**
     * Set create price quote flag
     *
     * @param bool $createPriceQuote Whether to create a price quote
     * @return self
     */
    public function setCreatePriceQuote(bool $createPriceQuote): self
    {
        $this->createPriceQuote = $createPriceQuote;
        return $this;
    }


    /**
     * Set transaction options
     *
     * @param array $options Transaction options
     * @return self
     */
    public function setTransactionOptions(array $options): self
    {
        $this->transactionOptions = $options;
        return $this;
    }

    /**
     * Set loyalty program account
     *
     * @param array $loyaltyProgram Loyalty program account
     * @return self
     */
    public function setarray(array $loyaltyProgram): self
    {
        $this->loyaltyProgramAccount = $loyaltyProgram;
        return $this;
    }

    /**
     * Set display payment card numbers flag
     *
     * @param bool $display Whether to display payment card numbers
     * @return self
     */
    public function setDisplayPaymentCardNumbers(bool $display): self
    {
        $this->displayPaymentCardNumbers = $display;
        return $this;
    }

    /**
     * Add airline remark
     *
     * @param string $id Remark ID
     * @param string $text Remark text
     * @param array $options Additional remark options
     * @return self
     */
    public function addAirlineRemark(string $id, string $text, array $options = []): self
    {
        $remark = [
            'id' => $id,
            'text' => $text
        ];

        if (isset($options['passengerRefIds'])) {
            $remark['passengerRefIds'] = $options['passengerRefIds'];
        }

        $this->airlineRemarks[] = $remark;
        return $this;
    }

    /**
     * Add seat
     *
     * @param string $offerItemId Seat offer item ID
     * @param array $options Seat options
     * @return self
     */
    public function addSeat(string $offerItemId, array $options = []): self
    {
        $seat = ['offerItemId' => $offerItemId];

        $optionalFields = [
            'passengerRefs',
            'segmentRefId',
            'row',
            'column'
        ];

        foreach ($optionalFields as $field) {
            if (isset($options[$field])) {
                $seat[$field] = $options[$field];
            }
        }

        $this->seatAdds[] = $seat;
        return $this;
    }

    /**
     * Validate the request
     *
     * @return bool
     * @throws SabreApiException
     */
    public function validate(): bool
    {
        if (empty($this->createOrders)) {
            throw new SabreApiException('At least one create order is required');
        }

        if (empty($this->contactInfos)) {
            throw new SabreApiException('At least one contact info is required');
        }

        if (empty($this->passengers)) {
            throw new SabreApiException('At least one passenger is required');
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
            'createOrders' => $this->createOrders,
            'contactInfos' => $this->contactInfos,
            'passengers' => $this->passengers
        ];

        // Add optional fields
        if ($this->party) {
            $request['party'] = $this->party;
        }

        if ($this->customerNumber) {
            $request['customerNumber'] = $this->customerNumber;
        }

        if ($this->createPriceQuote !== null) {
            $request['createPriceQuote'] = $this->createPriceQuote;
        }

        if ($this->transactionOptions) {
            $request['transactionOptions'] = $this->transactionOptions;
        }

        if ($this->loyaltyProgramAccount) {
            $request['loyaltyProgramAccount'] = $this->loyaltyProgramAccount;
        }

        if ($this->displayPaymentCardNumbers !== null) {
            $request['displayPaymentCardNumbers'] = $this->displayPaymentCardNumbers;
        }

        if (!empty($this->airlineRemarks)) {
            $request['airlineRemarks'] = $this->airlineRemarks;
        }

        if (!empty($this->seatAdds)) {
            $request['seatAdds'] = $this->seatAdds;
        }

        return $request;
    }
}