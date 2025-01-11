<?php

namespace Santosdave\SabreWrapper\Http\Rest;

use GuzzleHttp\Client;
use Santosdave\SabreWrapper\Contracts\SabreAuthenticatable;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Services\Core\RetryService;

class RestClient
{
    private Client $client;
    private array $defaultHeaders;

    private RetryService $retryService;

    public function __construct(
        private SabreAuthenticatable $auth,
        private string $environment = 'cert'
    ) {
        $this->setupClient();
        $this->setupDefaultHeaders();
        $this->retryService = new RetryService();
    }

    private function setupClient(): void
    {
        $this->client = new Client([
            'base_uri' => config("sabre.endpoints.{$this->environment}.rest"),
            'http_errors' => false,
            'timeout' => config('sabre.request.timeout', 30)
        ]);
    }

    private function setupDefaultHeaders(): void
    {
        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    private function request(string $method, string $endpoint, array $options = []): array
    {
        return $this->retryService->execute(function () use ($method, $endpoint, $options) {
            $options['headers'] = array_merge(
                $this->defaultHeaders,
                ['Authorization' => $this->auth->getAuthorizationHeader()],
                $options['headers'] ?? []
            );

            $response = $this->client->request($method, $endpoint, $options);
            $body = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                throw new SabreApiException(
                    "Sabre API error: " . $body,
                    $statusCode,
                    json_decode($body, true),
                    $response->getHeaderLine('X-Request-Id')
                );
            }

            return json_decode($body, true) ?? [];
        }, [
            'method' => $method,
            'endpoint' => $endpoint,
            'environment' => $this->environment
        ]);
    }

    public function setRetryConfig(array $config): void
    {
        $this->retryService->setConfig($config);
    }
}
