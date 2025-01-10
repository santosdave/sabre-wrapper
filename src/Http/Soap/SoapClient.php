<?php

namespace Santosdave\Sabre\Http\Soap;

use Santosdave\Sabre\Contracts\SabreAuthenticatable;
use Santosdave\Sabre\Exceptions\SabreApiException;

class SoapClient
{
    private \SoapClient $client;
    private XMLBuilder $xmlBuilder;

    public function __construct(
        private SabreAuthenticatable $auth,
        private string $environment = 'cert'
    ) {
        $this->setupClient();
        $this->xmlBuilder = new XMLBuilder();
    }

    private function setupClient(): void
    {
        $options = [
            'trace' => true,
            'exception' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
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
        try {
            $envelope = $this->xmlBuilder
                ->setAction($action)
                ->setToken($this->auth->getToken())
                ->setPayload($payload)
                ->build();

            $response = $this->client->__doRequest(
                $envelope,
                config("sabre.endpoints.{$this->environment}.soap"),
                $action,
                SOAP_1_1
            );

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP request failed: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    private function parseResponse(string $response): array
    {
        // Convert XML response to array
        $xml = simplexml_load_string($response);
        $json = json_encode($xml);
        return json_decode($json, true);
    }
}