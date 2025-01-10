<?php

namespace Santosdave\Sabre\Http\Rest;

use GuzzleHttp\Client;
use Santosdave\Sabre\Contracts\SabreAuthenticatable;
use Santosdave\Sabre\Exceptions\SabreApiException;

class RestClient
{
    private Client $client;
    private array $defaultHeaders;

    public function __construct(
        private SabreAuthenticatable $auth,
        private string $environment = 'cert'
    ) {
        $this->setupClient();
        $this->setupDefaultHeaders();
    }

    private function setupClient(): void
    {
        $this->client = new Client([
            'base_uri' => config("sabre.endpoints.{$this->environment}.rest"),
            'http_errors' => false
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
        try {
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
                    $statusCode
                );
            }

            return json_decode($body, true) ?? [];
        } catch (\Exception $e) {
            if ($e instanceof SabreApiException) {
                throw $e;
            }

            throw new SabreApiException(
                "Failed to make request to Sabre API: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}