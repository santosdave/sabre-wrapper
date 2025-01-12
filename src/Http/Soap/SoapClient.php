<?php

namespace Santosdave\SabreWrapper\Http\Soap;

use Santosdave\SabreWrapper\Contracts\Auth\TokenManagerInterface;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Services\Core\RetryService;
use Santosdave\SabreWrapper\Http\Soap\XMLBuilder;
use Santosdave\SabreWrapper\Services\Logging\SabreLogger;

class SoapClient
{
    private \SoapClient $client;
    private RetryService $retryService;
    private XMLBuilder $xmlBuilder;

    public function __construct(
        private TokenManagerInterface $auth,
        private string $environment = 'cert',
        private ?SabreLogger $logger = null
    ) {
        $this->setupClient();
        $this->retryService = new RetryService();
        $this->xmlBuilder = new XMLBuilder();
        $this->logger = $logger ?? app(SabreLogger::class);
    }

    private function setupClient(): void
    {
        $options = [
            'trace' => true,
            'exception' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'connection_timeout' => config('sabre.request.timeout', 30),
            'stream_context' => stream_context_create([
                'http' => [
                    'header' => $this->getDefaultHeaders()
                ]
            ])
        ];

        $this->client = new \SoapClient(null, $options);
        $this->client->__setLocation(
            config("sabre.endpoints.{$this->environment}.soap")
        );
    }

    public function send(string $action, array $data): array
    {
        $startTime = microtime(true);
        $requestId = uniqid('soap_');

        return $this->retryService->execute(function () use ($action, $data, $startTime, $requestId) {
            try {
                // Get appropriate token type
                $tokenType = $this->getTokenType($action);
                $token = $this->auth->getToken($tokenType);

                // Build SOAP envelope
                $envelope = $this->xmlBuilder
                    ->setAction($action)
                    ->setToken($token)
                    ->setRequestId($requestId)
                    ->setPayload($data)
                    ->build();

                // Log request
                $this->logger->logRequest(
                    'SOAP',
                    $action,
                    [
                        'envelope' => $this->sanitizeXml($envelope),
                        'token_type' => $tokenType,
                        'request_id' => $requestId
                    ]
                );

                // Send request
                $response = $this->client->__doRequest(
                    $envelope,
                    config("sabre.endpoints.{$this->environment}.soap"),
                    $action,
                    SOAP_1_1
                );

                $duration = microtime(true) - $startTime;

                if (!$response) {
                    throw new SabreApiException('Empty SOAP response received', 500, null, $requestId);
                }

                // Parse response
                $parsedResponse = $this->parseResponse($response);

                // Log response
                $this->logger->logResponse(
                    'SOAP',
                    $action,
                    [
                        'response' => $parsedResponse,
                        'raw_response' => $this->sanitizeXml($response),
                        'duration_ms' => round($duration * 1000, 2),
                        'request_id' => $requestId
                    ],
                    $duration
                );

                return $parsedResponse;
            } catch (\Exception $e) {
                $this->logger->logError($e, [
                    'request_id' => $requestId,
                    'action' => $action,
                    'data' => $this->sanitizeData($data),
                    'last_request' => $this->getLastRequest(),
                    'last_response' => $this->getLastResponse()
                ]);
                throw $e;
            }
        });
    }

    private function sanitizeXml(string $xml): string
    {
        $patterns = [
            '/<(Password|Token|SecurityToken)>(.*?)<\/(Password|Token|SecurityToken)>/i' => '<$1>********</$1>',
            '/<(CardNumber|CVV|SecurityCode)>(.*?)<\/(CardNumber|CVV|SecurityCode)>/i' => '<$1>********</$1>'
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $xml);
    }

    private function sanitizeData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'token',
            'securityToken',
            'cardNumber',
            'cvv',
            'securityCode',
            'apiKey',
            'secret'
        ];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveFields) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $value = '********';
            }
        });

        return $data;
    }

    private function getTokenType(string $action): string
    {
        return in_array($action, ['SessionCreateRQ', 'SessionCloseRQ'])
            ? 'soap_session'
            : 'soap_stateless';
    }

    private function getDefaultHeaders(): array
    {
        return [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: ""'
        ];
    }

    private function parseResponse(string $response): array
    {
        try {
            $xml = new \SimpleXMLElement($response);

            // Check for SOAP Fault
            if (isset($xml->Body->Fault)) {
                throw new SabreApiException(
                    (string)$xml->Body->Fault->faultstring,
                    500,
                    ['fault_code' => (string)$xml->Body->Fault->faultcode]
                );
            }

            // Convert to array
            $json = json_encode($xml);
            return json_decode($json, true);
        } catch (\Exception $e) {
            throw new SabreApiException(
                'Failed to parse SOAP response: ' . $e->getMessage(),
                500
            );
        }
    }

    private function getLastRequest(): ?array
    {
        try {
            return [
                'headers' => $this->client->__getLastRequestHeaders(),
                'body' => $this->client->__getLastRequest()
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getLastResponse(): ?array
    {
        try {
            return [
                'headers' => $this->client->__getLastResponseHeaders(),
                'body' => $this->client->__getLastResponse()
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}