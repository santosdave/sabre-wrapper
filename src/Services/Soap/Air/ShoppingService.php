<?php

namespace Santosdave\Sabre\Services\Soap\Air;

use Santosdave\Sabre\Services\Base\BaseSoapService;
use Santosdave\Sabre\Contracts\Services\AirShoppingServiceInterface;
use Santosdave\Sabre\Models\Air\BargainFinderMaxRequest;
use Santosdave\Sabre\Models\Air\BargainFinderMaxResponse;
use Santosdave\Sabre\Exceptions\SabreApiException;

class ShoppingService extends BaseSoapService implements AirShoppingServiceInterface
{
    public function bargainFinderMax(BargainFinderMaxRequest $request): BargainFinderMaxResponse
    {
        try {
            $response = $this->client->send(
                'BargainFinderMaxRQ',
                array_merge($request->toArray(), ['version' => '6.1.0'])
            );
            return new BargainFinderMaxResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to execute Bargain Finder Max search: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function alternativeDatesSearch(BargainFinderMaxRequest $request): BargainFinderMaxResponse
    {
        try {
            $response = $this->client->send(
                'BargainFinderMaxRQ',
                array_merge($request->toArray(), [
                    'version' => '6.1.0',
                    'TPA_Extensions' => [
                        'IntelliSellTransaction' => [
                            'RequestType' => [
                                'Name' => 'AD3'
                            ]
                        ]
                    ]
                ])
            );
            return new BargainFinderMaxResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to execute alternative dates search: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function instaFlights(
        string $origin,
        string $destination,
        string $departureDate,
        ?string $returnDate = null,
        int $limit = 50
    ): array {
        throw new SabreApiException("InstaFlights is only available via REST API");
    }
}