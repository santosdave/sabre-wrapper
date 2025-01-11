<?php

namespace Santosdave\SabreWrapper\Services\Base;

use Santosdave\SabreWrapper\Contracts\SabreAuthenticatable;
use Santosdave\SabreWrapper\Http\Soap\SoapClient;

abstract class BaseSoapService
{
    protected SoapClient $client;

    public function __construct(
        protected SabreAuthenticatable $auth,
        protected string $environment = 'cert'
    ) {
        $this->client = new SoapClient($auth, $environment);
    }

    protected function handleResponse(array $response): array
    {
        // Add common SOAP response handling logic here
        return $response;
    }
}
