<?php

namespace Santosdave\SabreWrapper\Models\Queue;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class QueuePlaceRequest implements SabreRequest
{
    private string $pnr;
    private string $queueNumber;
    private ?string $category = null;
    private ?string $pseudoCityCode = null;
    private ?array $remarks = null;

    public function __construct(string $pnr, string $queueNumber)
    {
        $this->pnr = $pnr;
        $this->queueNumber = $queueNumber;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function setPseudoCityCode(?string $pcc): self
    {
        $this->pseudoCityCode = $pcc;
        return $this;
    }

    public function addRemark(string $remark, string $type = 'General'): self
    {
        if (!$this->remarks) {
            $this->remarks = [];
        }
        $this->remarks[] = [
            'text' => $remark,
            'type' => $type
        ];
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->pnr)) {
            throw new SabreApiException('PNR is required');
        }

        if (empty($this->queueNumber)) {
            throw new SabreApiException('Queue number is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        $request = [
            'QueuePlaceRQ' => [
                'QueueInfo' => [
                    'QueueIdentifier' => [
                        'Number' => $this->queueNumber
                    ]
                ],
                'RecordLocator' => $this->pnr
            ]
        ];

        if ($this->category) {
            $request['QueuePlaceRQ']['QueueInfo']['QueueIdentifier']['Category'] =
                $this->category;
        }

        if ($this->pseudoCityCode) {
            $request['QueuePlaceRQ']['QueueInfo']['PseudoCityCode'] =
                $this->pseudoCityCode;
        }

        if ($this->remarks) {
            $request['QueuePlaceRQ']['Remarks'] = array_map(function ($remark) {
                return [
                    'Text' => $remark['text'],
                    'Type' => $remark['type']
                ];
            }, $this->remarks);
        }

        return $request;
    }
}
