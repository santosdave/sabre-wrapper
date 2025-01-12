<?php

namespace Santosdave\SabreWrapper\Services\Base;

use Santosdave\SabreWrapper\Contracts\Auth\TokenManagerInterface;
use Santosdave\SabreWrapper\Http\Rest\RestClient;
use Santosdave\SabreWrapper\Services\Logging\SabreLogger;

abstract class BaseRestService
{
    protected RestClient $client;

    protected SabreLogger $logger;

    public function __construct(
        protected TokenManagerInterface $auth,
        protected string $environment = 'cert',
        ?SabreLogger $logger = null
    ) {
        $this->client = new RestClient($auth, $environment, $logger);
        $this->logger = $logger ?? app(SabreLogger::class);

        // Set service context
        $this->logger->setContext([
            'service' => $this->getServiceName(),
            'environment' => $environment
        ]);
    }

    protected function handleResponse(array $response): array
    {
        $this->logger->debug('Service response handled', [
            'service' => $this->getServiceName(),
            'response' => $response
        ]);
        return $response;
    }

    protected function getServiceName(): string
    {
        $class = get_class($this);
        $parts = explode('\\', $class);
        return end($parts);
    }
}