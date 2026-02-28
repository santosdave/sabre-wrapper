<?php

namespace Santosdave\SabreWrapper\Models\Air;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class CreatePnrResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private ?string $pnr = null;
    private array $data;

    private string $defaultAuthMethod;

    public function __construct(array $response, string $type)
    {
        $this->defaultAuthMethod = config('sabre.auth.default_method', 'rest');
        $type = $type ?? $this->defaultAuthMethod;
        $this->parseResponse($response, $type);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getPnr(): ?string
    {
        return $this->pnr;
    }

    private function parseResponse(array $response, string $type): void
    {
        $this->data = $response;

        if ($type === 'soap') {
            $this->parseSoapResponse($response);
        } else {
            $this->parseRestResponse($response);
        }
    }

    private function parseSoapResponse(array $response): void
    {
        if (isset($response['CreatePassengerNameRecordRS'])) {
            $rs = $response['CreatePassengerNameRecordRS'];
            $this->success = true;

            if (isset($rs['ItineraryRef']['ID'])) {
                $this->pnr = $rs['ItineraryRef']['ID'];
            }

            if (isset($rs['ApplicationResults']['Error'])) {
                $this->success = false;
                $this->errors = array_map(function ($error) {
                    return $error['SystemSpecificResults']['Message'];
                }, (array) $rs['ApplicationResults']['Error']);
            }
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid SOAP response format';
        }
    }

    private function parseRestResponse(array $response): void
    {
        if (isset($response['CreatePassengerNameRecordResponse'])) {
            // Original REST format
            $rs = $response['CreatePassengerNameRecordResponse'];
            $this->success = true;

            if (isset($rs['pnr'])) {
                $this->pnr = $rs['pnr'];
            }

            if (isset($rs['errors'])) {
                $this->success = false;
                $this->errors = $rs['errors'];
            }
        } elseif (isset($response['CreatePassengerNameRecordRS'])) {
            // SOAP-style REST format
            $rs = $response['CreatePassengerNameRecordRS'];
            $this->data = $rs;
            $status = $rs['ApplicationResults']['status'] ?? '';

            if ($status === 'Complete') {
                $this->success = true;
                // PNR can be in two places depending on response depth
                $this->pnr = $rs['ItineraryRef']['ID']
                    ?? $rs['TravelItineraryRead']['TravelItinerary']['ItineraryRef']['ID']
                    ?? null;
            } else {
                $this->success = false;
                $errors = $rs['ApplicationResults']['Error'] ?? [];
                foreach ((array) $errors as $error) {
                    $results = $error['SystemSpecificResults'] ?? [];
                    foreach ((array) $results as $result) {
                        $messages = $result['Message'] ?? [];
                        foreach ((array) $messages as $msg) {
                            $this->errors[] = is_array($msg)
                                ? ($msg['content'] ?? json_encode($msg))
                                : $msg;
                        }
                    }
                }
                if (empty($this->errors)) {
                    $this->errors[] = $status ?: 'PNR creation failed';
                }
            }
        } else {
            $this->success = false;
            $this->errors[] = 'Invalid REST response format';
        }
    }
}
