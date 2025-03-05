<?php

namespace Santosdave\SabreWrapper\Models\Air\Seat;

use Santosdave\SabreWrapper\Contracts\SabreResponse;

class SeatMapResponse implements SabreResponse
{
    private bool $success;
    private array $errors = [];
    private array $data;
    private string $source;

    private array $seatMaps = [];
    private ?array $aLaCarteOffer = null;
    private ?array $shoppingResponse = null;

    public function __construct(array $response, string $source = 'NDC')
    {
        $this->source = $source;
        $this->parseResponse($response);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getSeatMaps(): array
    {
        return $this->seatMaps;
    }

    public function getALaCarteOffer(): ?array
    {
        return $this->aLaCarteOffer;
    }

    public function getShoppingResponse(): ?array
    {
        return $this->shoppingResponse;
    }

    private function parseResponse(array $response): void
    {
        $this->data = $response;

        // Check for errors
        if (isset($response['errors']) && !empty($response['errors'])) {
            $this->success = false;
            $this->errors = $this->parseErrors($response['errors']);
            return;
        }

        // Validate response based on source
        switch ($this->source) {
            case 'NDC':
                $this->parseNDCResponse($response);
                break;
            case 'ATPCO':
                $this->parseATPCOResponse($response);
                break;
            case 'LCC':
                $this->parseLCCResponse($response);
                break;
            default:
                $this->parseGenericResponse($response);
        }
    }

    private function parseNDCResponse(array $response): void
    {
        $seatAvailabilityResponse = $response['response'] ?? $response;

        // Set success status
        $this->success = isset($seatAvailabilityResponse['seatMaps']);

        // Parse A La Carte Offer
        if (isset($seatAvailabilityResponse['aLaCarteOffer'])) {
            $this->aLaCarteOffer = $this->parseALaCarteOffer($seatAvailabilityResponse['aLaCarteOffer']);
        }

        // Parse Shopping Response
        if (isset($seatAvailabilityResponse['shoppingResponse'])) {
            $this->shoppingResponse = $this->parseShoppingResponse($seatAvailabilityResponse['shoppingResponse']);
        }

        // Parse Seat Maps
        if (isset($seatAvailabilityResponse['seatMaps'])) {
            $this->seatMaps = array_map([$this, 'parseSeatMap'], $seatAvailabilityResponse['seatMaps']);
        }
    }

    private function parseATPCOResponse(array $response): void
    {
        // ATPCO-specific parsing logic
        $this->parseNDCResponse($response);
    }

    private function parseLCCResponse(array $response): void
    {
        // Low Cost Carrier specific parsing logic
        $this->parseNDCResponse($response);
    }

    private function parseGenericResponse(array $response): void
    {
        // Fallback parsing method
        $this->parseNDCResponse($response);
    }

    private function parseErrors(array $errors): array
    {
        return array_map(function ($error) {
            return [
                'code' => $error['code'] ?? null,
                'message' => $error['descriptionText'] ?? 'Unknown error',
                'type' => $error['typeCode'] ?? null
            ];
        }, $errors);
    }

    private function parseSeatMap(array $seatMap): array
    {
        $parsedSeatMap = [
            'segment_ref_id' => $seatMap['paxSegmentRefID'] ?? null,
            'sellable' => $seatMap['sellable'] ?? false,
            'cabins' => []
        ];

        // Parse cabin compartments
        if (isset($seatMap['cabinCompartments'])) {
            $parsedSeatMap['cabins'] = array_map(function ($cabin) {
                return [
                    'deck_code' => $cabin['deckCode'] ?? null,
                    'first_row' => $cabin['firstRow'] ?? null,
                    'last_row' => $cabin['lastRow'] ?? null,
                    'cabin_type' => $cabin['cabinType']['cabinTypeCode'] ?? null,
                    'seats' => $this->parseSeats($cabin)
                ];
            }, $seatMap['cabinCompartments']);
        }

        return $parsedSeatMap;
    }

    private function parseSeats(array $cabin): array
    {
        $seats = [];

        if (isset($cabin['seatRows'])) {
            foreach ($cabin['seatRows'] as $row) {
                foreach ($row['seats'] as $seat) {
                    $seats[] = [
                        'row' => $row['row'],
                        'column' => $seat['column'],
                        'characteristics' => $seat['characteristics']
                            ? array_column($seat['characteristics'], 'code')
                            : [],
                        'occupation_status' => $seat['occupationStatusCode'] ?? null,
                        'offer_item_ref_ids' => $seat['offerItemRefIDs'] ?? []
                    ];
                }
            }
        }

        return $seats;
    }

    private function parseALaCarteOffer(array $aLaCarteOffer): array
    {
        return [
            'offer_id' => $aLaCarteOffer['offerId'] ?? null,
            'owner_code' => $aLaCarteOffer['ownerCode'] ?? null,
            'total_price' => $aLaCarteOffer['totalPrice'] ?? null,
            'offer_items' => $aLaCarteOffer['aLaCarteOfferItems'] ?? []
        ];
    }

    private function parseShoppingResponse(array $shoppingResponse): array
    {
        return [
            'shopping_response_id' => $shoppingResponse['shoppingResponseID'] ?? null,
            'owner_code' => $shoppingResponse['ownerCode'] ?? null
        ];
    }
}
