<?php

namespace Santosdave\SabreWrapper\Models\Air\Order;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class OrderChangeRequest implements SabreRequest
{
    private string $orderId;
    private ?string $offerId = null;
    private ?string $requestType = null;
    private ?bool $displayPaymentCardNumbers = null;
    private ?bool $cancelWithRetain = null;
    private ?bool $cancelOrderAndRetainDocument = null;
    private ?bool $cancelDocumentAndRetainOrder = null;
    private array $actions = [];
    private ?array $party = null;
    private array $seatAdds = [];
    private array $seatDeletes = [];
    private array $contactInformationAdds = [];
    private array $contactInformationUpdates = [];
    private array $contactInformationDeletes = [];
    private array $loyaltyProgramAccountAdds = [];
    private array $loyaltyProgramAccountDeletes = [];
    private array $identityDocumentAdds = [];
    private array $identityDocumentUpdates = [];
    private array $identityDocumentDeletes = [];
    private array $serviceAdds = [];
    private array $passengerUpdates = [];
    private array $orderItemUpdates = [];
    private array $orderItemDeletes = [];
    private array $airlineRemarkAdds = [];
    private ?string $mode = null;
    private array $auxiliaryActions = [];

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function setOfferId(string $offerId): self
    {
        $this->offerId = $offerId;
        return $this;
    }

    public function setRequestType(string $requestType): self
    {
        $this->requestType = $requestType;
        return $this;
    }

    public function setDisplayPaymentCardNumbers(bool $display): self
    {
        $this->displayPaymentCardNumbers = $display;
        return $this;
    }

    public function setCancelWithRetain(bool $cancelWithRetain): self
    {
        $this->cancelWithRetain = $cancelWithRetain;
        return $this;
    }

    public function setCancelOrderAndRetainDocument(bool $cancel): self
    {
        $this->cancelOrderAndRetainDocument = $cancel;
        return $this;
    }

    public function setCancelDocumentAndRetainOrder(bool $cancel): self
    {
        $this->cancelDocumentAndRetainOrder = $cancel;
        return $this;
    }

    public function addAction(array $action): self
    {
        $this->actions[] = $action;
        return $this;
    }

    public function addFulfillOrderAction(array $paymentInfo, array $orderItemRefIds = []): self
    {
        $this->actions[] = [
            'fulfillOrder' => array_merge(
                ['paymentInfo' => $paymentInfo],
                $orderItemRefIds ? ['orderItemRefIds' => $orderItemRefIds] : []
            )
        ];
        return $this;
    }

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

    public function addSeat(array $seatDetails): self
    {
        $this->seatAdds[] = $seatDetails;
        return $this;
    }

    public function addContactInformation(array $contactInfo): self
    {
        $this->contactInformationAdds[] = $contactInfo;
        return $this;
    }

    public function addLoyaltyProgramAccount(array $loyaltyAccount): self
    {
        $this->loyaltyProgramAccountAdds[] = $loyaltyAccount;
        return $this;
    }

    public function addIdentityDocument(array $documentDetails): self
    {
        $this->identityDocumentAdds[] = $documentDetails;
        return $this;
    }

    public function addService(array $serviceDetails): self
    {
        $this->serviceAdds[] = $serviceDetails;
        return $this;
    }

    public function addPassengerUpdate(array $updateDetails): self
    {
        $this->passengerUpdates[] = $updateDetails;
        return $this;
    }


    public function validate(): bool
    {
        if (empty($this->orderId)) {
            throw new SabreApiException('Order ID is required');
        }

        if (empty($this->actions)) {
            throw new SabreApiException('At least one action is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'id' => $this->orderId,
            'actions' => $this->actions
        ];

        // Optional fields
        if ($this->offerId) {
            $request['offerId'] = $this->offerId;
        }

        if ($this->requestType) {
            $request['requestType'] = $this->requestType;
        }

        if ($this->displayPaymentCardNumbers !== null) {
            $request['displayPaymentCardNumbers'] = $this->displayPaymentCardNumbers;
        }

        if ($this->cancelWithRetain !== null) {
            $request['cancelWithRetain'] = $this->cancelWithRetain;
        }

        if ($this->cancelOrderAndRetainDocument !== null) {
            $request['cancelOrderAndRetainDocument'] = $this->cancelOrderAndRetainDocument;
        }

        if ($this->cancelDocumentAndRetainOrder !== null) {
            $request['cancelDocumentAndRetainOrder'] = $this->cancelDocumentAndRetainOrder;
        }

        if ($this->party) {
            $request['party'] = $this->party;
        }

        // Add other optional arrays
        $optionalArrays = [
            'seatAdds',
            'seatDeletes',
            'contactInformationAdds',
            'contactInformationUpdates',
            'contactInformationDeletes',
            'loyaltyProgramAccountAdds',
            'loyaltyProgramAccountDeletes',
            'identityDocumentAdds',
            'identityDocumentUpdates',
            'identityDocumentDeletes',
            'serviceAdds',
            'passengerUpdates',
            'orderItemUpdates',
            'orderItemDeletes',
            'airlineRemarkAdds',
            'auxiliaryActions'
        ];

        foreach ($optionalArrays as $arrayName) {
            if (!empty($this->{$arrayName})) {
                $request[$arrayName] = $this->{$arrayName};
            }
        }

        if ($this->mode) {
            $request['mode'] = $this->mode;
        }

        return $request;
    }
}