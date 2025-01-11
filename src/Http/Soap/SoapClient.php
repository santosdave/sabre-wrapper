<?php

namespace Santosdave\Sabre\Http\Soap;

use Santosdave\Sabre\Contracts\SabreAuthenticatable;
use Santosdave\Sabre\Exceptions\SabreApiException;
use Santosdave\Sabre\Http\Soap\XMLBuilder;
use Santosdave\Sabre\Services\Core\RetryService;

class SoapClient
{
    private \SoapClient $client;
    private XMLBuilder $xmlBuilder;

    private RetryService $retryService;

    public function __construct(
        private SabreAuthenticatable $auth,
        private string $environment = 'cert'
    ) {
        $this->setupClient();
        $this->xmlBuilder = new XMLBuilder();
        $this->retryService = new RetryService();
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
                    'header' => [
                        'Content-Type: text/xml; charset=utf-8',
                    ]
                ]
            ])
        ];

        $this->client = new \SoapClient(null, $options);
        $this->client->__setLocation(config("sabre.endpoints.{$this->environment}.soap"));
    }

    public function send(string $action, array $payload): array
    {
        return $this->retryService->execute(function () use ($action, $payload) {
            $envelope = $this->xmlBuilder
                ->setAction($action)
                ->setToken($this->auth->getToken('soap_stateless'))
                ->setPayload($payload)
                ->build();

            $response = $this->client->__doRequest(
                $envelope,
                config("sabre.endpoints.{$this->environment}.soap"),
                $action,
                SOAP_1_1
            );

            if (!$response) {
                throw new SabreApiException('Empty SOAP response received');
            }

            return $this->parseResponse($response);
        }, [
            'action' => $action,
            'environment' => $this->environment
        ]);
    }

    private function parseResponse(string $response): array
    {
        // Convert XML response to array
        try {
            $xml = simplexml_load_string($response);

            // Check for SOAP Fault
            if (isset($xml->Body->Fault)) {
                throw new SabreApiException(
                    (string)$xml->Body->Fault->faultstring,
                    500,
                    ['fault_code' => (string)$xml->Body->Fault->faultcode]
                );
            }

            $json = json_encode($xml);
            return json_decode($json, true);
        } catch (\Exception $e) {
            throw new SabreApiException(
                'Failed to parse SOAP response: ' . $e->getMessage(),
                500,
                null,
                null,
                $e
            );
        }
    }

    public function setRetryConfig(array $config): void
    {
        $this->retryService->setConfig($config);
    }
}