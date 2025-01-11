<?php

namespace Santosdave\SabreWrapper\Services\Rest\Air;

use Santosdave\SabreWrapper\Services\Base\BaseRestService;
use Santosdave\SabreWrapper\Contracts\Services\AirShoppingServiceInterface;
use Santosdave\SabreWrapper\Models\Air\BargainFinderMaxRequest;
use Santosdave\SabreWrapper\Models\Air\BargainFinderMaxResponse;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Helpers\CacheableRequest;

class ShoppingService extends BaseRestService implements AirShoppingServiceInterface
{

    use CacheableRequest;

    public function bargainFinderMax(BargainFinderMaxRequest $request): BargainFinderMaxResponse
    {
        $cacheKey = $this->generateCacheKey([
            'method' => 'bargainFinderMax',
            'params' => $request->toArray()
        ]);

        return $this->withCache(
            $cacheKey,
            function () use ($request) {
                try {
                    $response = $this->client->post(
                        '/v3/offers/shop',
                        $request->toArray()
                    );
                    return new BargainFinderMaxResponse($response);
                } catch (\Exception $e) {
                    throw new SabreApiException(
                        "Failed to execute Bargain Finder Max search: " . $e->getMessage(),
                        $e->getCode()
                    );
                }
            },
            'shopping.results'
        );
    }

    public function alternativeDatesSearch(BargainFinderMaxRequest $request): BargainFinderMaxResponse
    {
        $cacheKey = $this->generateCacheKey([
            'method' => 'alternativeDatesSearch',
            'params' => $request->toArray()
        ]);

        return $this->withCache(
            $cacheKey,
            function () use ($request) {
                try {
                    $response = $this->client->post(
                        '/v3/offers/shop',
                        array_merge($request->toArray(), [
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
                        "Failed to execute alternative dates search: " . $e->getMessage(),
                        $e->getCode()
                    );
                }
            },
            'shopping.alternative_dates',
            1800 // 30 minutes cache
        );
    }

    public function instaFlights(
        string $origin,
        string $destination,
        string $departureDate,
        ?string $returnDate = null,
        int $limit = 50
    ): array {
        $cacheKey = $this->generateCacheKey([
            'method' => 'instaFlights',
            'origin' => $origin,
            'destination' => $destination,
            'departureDate' => $departureDate,
            'returnDate' => $returnDate,
            'limit' => $limit
        ]);

        return $this->withCache(
            $cacheKey,
            function () use ($origin, $destination, $departureDate, $returnDate, $limit) {
                try {
                    $params = [
                        'origin' => $origin,
                        'destination' => $destination,
                        'departuredate' => $departureDate,
                        'limit' => $limit,
                        'sortby' => 'totalfare',
                        'order' => 'asc',
                        'pointofsalecountry' => 'US'
                    ];

                    if ($returnDate) {
                        $params['returndate'] = $returnDate;
                    }

                    return $this->client->get('/v1/shop/flights', $params);
                } catch (\Exception $e) {
                    throw new SabreApiException(
                        "Failed to execute InstaFlights search: " . $e->getMessage(),
                        $e->getCode()
                    );
                }
            },
            'shopping.insta_flights',
            900 // 15 minutes cache
        );
    }
}
