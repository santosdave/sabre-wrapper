<?php

namespace Santosdave\Sabre\Models\Queue;

use Santosdave\Sabre\Contracts\SabreRequest;
use Santosdave\Sabre\Exceptions\SabreApiException;

class QueueRemoveRequest implements SabreRequest
{
    private string $queueNumber;
    private ?string $category = null;
    private ?array $pnrs = null;
    private ?string $pseudoCityCode = null;

    public function __construct(string $queueNumber)
    {
        $this->queueNumber = $queueNumber;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function setPnrs(array $pnrs): self
    {
        $this->pnrs = $pnrs;
        return $this;
    }

    public function setPseudoCityCode(?string $pcc): self
    {
        $this->pseudoCityCode = $pcc;
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
            'QueueRemoveRQ' => [
                'QueueIdentifier' => [
                    'Number' => $this->queueNumber
                ]
            ]
        ];

        if ($this->category) {
            $request['QueueRemoveRQ']['QueueIdentifier']['Category'] = $this->category;
        }

        if ($this->pnrs) {
            $request['QueueRemoveRQ']['RecordLocators'] = array_map(function ($pnr) {
                return ['RecordLocator' => $pnr];
            }, $this->pnrs);
        }

        if ($this->pseudoCityCode) {
            $request['QueueRemoveRQ']['PseudoCityCode'] = $this->pseudoCityCode;
        }

        return $request;
    }
}
