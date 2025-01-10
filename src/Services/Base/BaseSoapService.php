<?php

namespace Santosdave\Sabre\Services\Base;

use Santosdave\Sabre\Contracts\SabreAuthenticatable;
use Santosdave\Sabre\Http\Soap\SoapClient;

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