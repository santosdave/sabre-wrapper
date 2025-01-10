<?php

namespace Santosdave\Sabre\Services\Rest\Air;

use Santosdave\Sabre\Services\Base\BaseRestService;
use Santosdave\Sabre\Contracts\Services\AirShoppingServiceInterface;
use Santosdave\Sabre\Models\Air\BargainFinderMaxRequest;
use Santosdave\Sabre\Models\Air\BargainFinderMaxResponse;
use Santosdave\Sabre\Exceptions\SabreApiException;

class ShoppingService extends BaseRestService implements AirShoppingServiceInterface
{
    public function bargainFinderMax(BargainFinderMaxRequest $request): BargainFinderMaxResponse
    {
        try {
            $response = $this->client->post('/v2/offers/shop', $request->toArray());
            return new BargainFinderMaxResponse($response);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "REST: Failed to execute Bargain Finder Max search: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function alternativeDatesSearch(BargainFinderMaxRequest $request): BargainFinderMaxResponse
    {
        try {
            $response = $this->client->post(
                '/v6.1.0/shop/altdates/flights?mode=live',
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
                "REST: Failed to execute alternative dates search: " . $e->getMessage(),
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
                "REST: Failed to execute InstaFlights search: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}