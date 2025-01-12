<?php

namespace Santosdave\SabreWrapper\Http\Rest;

use GuzzleHttp\Client;
use Santosdave\SabreWrapper\Contracts\Auth\TokenManagerInterface;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Services\Core\RetryService;
use Santosdave\SabreWrapper\Services\Logging\SabreLogger;

class RestClient
{
    private Client $client;
    private RetryService $retryService;

    public function __construct(
        private TokenManagerInterface $auth,
        private string $environment = 'cert',
        private ?SabreLogger $logger = null
    ) {
        $this->setupClient();
        $this->retryService = new RetryService();
        $this->logger = $logger ?? app(SabreLogger::class);
    }

    private function setupClient(): void
    {
        $this->client = new Client([
            'base_uri' => config("sabre.endpoints.{$this->environment}.rest"),
            'http_errors' => false,
            'timeout' => config('sabre.request.timeout', 30)
        ]);
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    public function request(string $method, string $endpoint, array $options = []): array
    {
        $startTime = microtime(true);
        $requestId = uniqid('req_');

        return $this->retryService->execute(function () use ($method, $endpoint, $options, $startTime, $requestId) {
            try {
                // Add headers including authorization
                $options['headers'] = array_merge(
                    $this->getDefaultHeaders(),
                    $options['headers'] ?? [],
                    ['X-Request-ID' => $requestId]
                );

                // Log request
                $this->logger->logRequest(
                    'REST',
                    $endpoint,
                    [
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'headers' => $this->sanitizeHeaders($options['headers']),
                        'body' => $options['json'] ?? null,
                        'request_id' => $requestId
                    ]
                );

                // Make request
                $response = $this->client->request($method, $endpoint, $options);
                $body = $response->getBody()->getContents();
                $duration = microtime(true) - $startTime;

                // Parse response
                $parsedResponse = json_decode($body, true) ?? [];

                // Log response
                $this->logger->logResponse(
                    'REST',
                    $endpoint,
                    [
                        'status' => $response->getStatusCode(),
                        'headers' => $response->getHeaders(),
                        'body' => $parsedResponse,
                        'duration_ms' => round($duration * 1000, 2),
                        'request_id' => $requestId
                    ],
                    $duration
                );

                // Handle error responses
                if ($response->getStatusCode() >= 400) {
                    throw new SabreApiException(
                        "Sabre API error: {$body}",
                        $response->getStatusCode(),
                        $parsedResponse,
                        $requestId
                    );
                }

                return $parsedResponse;
            } catch (\Exception $e) {
                $this->logger->logError($e, [
                    'request_id' => $requestId,
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'options' => $this->sanitizeOptions($options)
                ]);
                throw $e;
            }
        });
    }

    private function getDefaultHeaders(): array
    {
        return [
            'Authorization' => $this->auth->getAuthorizationHeader('rest'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['Authorization', 'apikey', 'api-key'];

        return array_map(function ($value, $key) use ($sensitiveHeaders) {
            if (in_array($key, $sensitiveHeaders, true)) {
                return '********';
            }
            return $value;
        }, $headers, array_keys($headers));
    }

    private function sanitizeOptions(array $options): array
    {
        $sanitized = $options;

        // Remove sensitive data from headers
        if (isset($sanitized['headers'])) {
            $sanitized['headers'] = $this->sanitizeHeaders($sanitized['headers']);
        }

        // Remove potentially sensitive body content
        if (isset($sanitized['json'])) {
            $sanitized['json'] = '[REDACTED]';
        }

        return $sanitized;
    }

    public function setRetryConfig(array $config): void
    {
        $this->retryService->setConfig($config);
    }
}