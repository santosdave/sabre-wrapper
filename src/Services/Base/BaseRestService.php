<?php

namespace Santosdave\SabreWrapper\Services\Base;

use Santosdave\SabreWrapper\Contracts\SabreAuthenticatable;
use Santosdave\SabreWrapper\Http\Rest\RestClient;

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
