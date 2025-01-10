<?php

namespace Santosdave\Sabre\Services\Base;

use Santosdave\Sabre\Contracts\SabreAuthenticatable;
use Santosdave\Sabre\Http\Rest\RestClient;

abstract class BaseRestService
{
    protected RestClient $client;

    public function __construct(
        protected SabreAuthenticatable $auth,
        protected string $environment = 'cert'
    ) {
        $this->client = new RestClient($auth, $environment);
    }

    protected function handleResponse(array $response): array
    {
        // Add common REST response handling logic here
        return $response;
    }
}