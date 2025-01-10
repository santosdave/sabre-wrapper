<?php

namespace Santosdave\Sabre\Models\Queue;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class QueueListRequest implements SabreRequest
{
    private string $queueNumber;
    private ?string $category = null;
    private ?array $dateRange = null;
    private ?array $carriers = null;
    private ?array $pseudoCityCodes = null;
    private ?bool $includePNRContent = false;

    public function __construct(string $queueNumber)
    {
        $this->queueNumber = $queueNumber;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function setDateRange(string $startDate, string $endDate): self
    {
        $this->dateRange = compact('startDate', 'endDate');
        return $this;
    }

    public function setCarriers(array $carriers): self
    {
        $this->carriers = $carriers;
        return $this;
    }

    public function setPseudoCityCodes(array $pccs): self
    {
        $this->pseudoCityCodes = $pccs;
        return $this;
    }

    public function setIncludePNRContent(bool $include): self
    {
        $this->includePNRContent = $include;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->queueNumber)) {
            throw new SabreApiException('Queue number is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'QueueAccessRQ' => [
                'QueueIdentifier' => [
                    'Number' => $this->queueNumber
                ]
            ]
        ];

        if ($this->category) {
            $request['QueueAccessRQ']['QueueIdentifier']['Category'] = $this->category;
        }

        if ($this->dateRange) {
            $request['QueueAccessRQ']['TimeRange'] = $this->dateRange;
        }

        if ($this->carriers) {
            $request['QueueAccessRQ']['Airlines'] = array_map(function ($carrier) {
                return ['Code' => $carrier];
            }, $this->carriers);
        }

        if ($this->pseudoCityCodes) {
            $request['QueueAccessRQ']['PseudoCityCode'] = $this->pseudoCityCodes;
        }

        if ($this->includePNRContent) {
            $request['QueueAccessRQ']['IncludePNRContent'] = true;
        }

        return $request;
    }
}