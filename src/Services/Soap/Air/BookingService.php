<?php

namespace Santosdave\SabreWrapper\Services\Soap\Air;

use Santosdave\SabreWrapper\Services\Base\BaseSoapService;
use Santosdave\SabreWrapper\Contracts\Services\AirBookingServiceInterface;
use Santosdave\SabreWrapper\Models\Air\CreatePnrRequest;
use Santosdave\SabreWrapper\Models\Air\CreatePnrResponse;
use Santosdave\SabreWrapper\Models\Air\EnhancedAirBookRequest;
use Santosdave\SabreWrapper\Models\Air\PassengerDetailsRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class BookingService extends BaseSoapService implements AirBookingServiceInterface
{
    public function createBooking(\Santosdave\SabreWrapper\Models\Air\Booking\CreateBookingRequest $request): \Santosdave\SabreWrapper\Models\Air\Booking\CreateBookingResponse
    {
        // Implement the createBooking method
        throw new \Exception('Method createBooking() is not implemented.');
    }

    public function getBooking(string $confirmationId): \Santosdave\SabreWrapper\Models\Air\Booking\CreateBookingResponse
    {
        // Implement the getBooking method
        throw new \Exception('Method getBooking() is not implemented.');
    }

    public function cancelBooking(string $confirmationId, bool $retrieveBooking = true, bool $cancelAll = true): \Santosdave\SabreWrapper\Models\Air\Order\OrderCancelResponse
    {
        // Implement the cancelBooking method
        throw new \Exception('Method cancelBooking() is not implemented.');
    }

    public function createPnr(CreatePnrRequest $request): CreatePnrResponse
    {
        try {
            $response = $this->client->send(
                'CreatePassengerNameRecordRQ',
                $request->toArray()
            );
            return new CreatePnrResponse($response, 'soap');
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to create PNR: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function enhancedAirBook(EnhancedAirBookRequest $request): array
    {
        try {
            return $this->client->send('EnhancedAirBookRQ', [
                'EnhancedAirBookRQ' => array_merge(
                    [
                        'version' => '3.10.0',
                        'HaltOnError' => true,
                        'IgnoreOnError' => true
                    ],
                    $this->buildEnhancedAirBookPayload($request)
                )
            ]);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to perform enhanced air book: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function addPassengerDetails(PassengerDetailsRequest $request): array
    {
        try {
            return $this->client->send('PassengerDetailsRQ', [
                'PassengerDetailsRQ' => [
                    'version' => '3.4.0',
                    'xmlns' => 'http://services.sabre.com/sp/pd/v3_4',
                    'ignoreOnError' => false,
                    'haltOnError' => false,
                    ...($request->toArray())
                ]
            ]);
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to add passenger details: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function cancelPnr(string $pnr): bool
    {
        try {
            // First retrieve the PNR to get it into context
            $this->client->send('GetReservationRQ', [
                'GetReservationRQ' => [
                    'ReturnOptions' => [
                        'ViewName' => 'Full'
                    ],
                    'Locator' => $pnr
                ]
            ]);

            // Then cancel it
            $response = $this->client->send('OTA_CancelRQ', [
                'OTA_CancelRQ' => [
                    'Version' => '2.0.2',
                    'CancelType' => 'Cancel',
                    'Segment' => [
                        'Type' => 'Entire'
                    ]
                ]
            ]);

            return isset($response['OTA_CancelRS']['Status']) &&
                $response['OTA_CancelRS']['Status'] === 'Complete';
        } catch (\Exception $e) {
            throw new SabreApiException(
                "SOAP: Failed to cancel PNR: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    private function buildEnhancedAirBookPayload(EnhancedAirBookRequest $request): array
    {
        $data = $request->toArray();

        $payload = [
            'OTA_AirBookRQ' => [
                'RetryRebook' => ['Option' => true],
                'HaltOnStatus' => [
                    ['Code' => 'HL'],
                    ['Code' => 'HN'],
                    ['Code' => 'HX'],
                    ['Code' => 'LL'],
                    ['Code' => 'NN'],
                    ['Code' => 'NO'],
                    ['Code' => 'PN'],
                    ['Code' => 'UC'],
                    ['Code' => 'UN'],
                    ['Code' => 'US'],
                    ['Code' => 'UU']
                ],
                'OriginDestinationInformation' => [
                    'FlightSegment' => array_map(function ($segment) {
                        return [
                            'DepartureDateTime' => $segment['departureDateTime'],
                            'FlightNumber' => $segment['flightNumber'],
                            'NumberInParty' => 1,
                            'ResBookDesigCode' => $segment['bookingClass'],
                            'Status' => 'NN',
                            'DestinationLocation' => [
                                'LocationCode' => $segment['destination']
                            ],
                            'MarketingAirline' => [
                                'Code' => $segment['carrier'],
                                'FlightNumber' => $segment['flightNumber']
                            ],
                            'OriginLocation' => [
                                'LocationCode' => $segment['origin']
                            ]
                        ];
                    }, $data['segments'])
                ],
                'RedisplayReservation' => [
                    'NumAttempts' => 10,
                    'WaitInterval' => 1000
                ]
            ]
        ];

        if ($request->priceItinerary) {
            $payload['OTA_AirPriceRQ'] = [
                'PriceRequestInformation' => [
                    'Retain' => true,
                    'OptionalQualifiers' => [
                        'PricingQualifiers' => [
                            'PassengerType' => array_map(function ($passenger) {
                                return [
                                    'Code' => $passenger['type'],
                                    'Quantity' => $passenger['quantity']
                                ];
                            }, $data['passengers'])
                        ]
                    ]
                ]
            ];
        }

        return $payload;
    }
}
