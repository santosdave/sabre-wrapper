<?php

namespace Santosdave\SabreWrapper\Models\Air;

use Santosdave\SabreWrapper\Contracts\SabreRequest;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;

class BargainFinderMaxRequest implements SabreRequest
{
    private string $pseudoCityCode;
    private array $originDestinationInformation = [];
    private array $travelPreferences = [];
    private array $travelerInfoSummary = [];
    private int $numTrips = 50;
    private bool $enableNDC = true;
    private bool $enableATPCO = false;
    private bool $enableLCC = false;
    private bool $preferNDCSourceOnTie = true;

    public function __construct(string $pseudoCityCode)
    {
        $this->pseudoCityCode = $pseudoCityCode;
    }

    public function addOriginDestination(
        string $origin,
        string $destination,
        string $departureDate,
        ?string $returnDate = null
    ): self {
        $segment = [
            'RPH' => (string)(count($this->originDestinationInformation) + 1),
            'DepartureDateTime' => $departureDate . 'T00:00:00',
            'OriginLocation' => [
                'LocationCode' => $origin
            ],
            'DestinationLocation' => [
                'LocationCode' => $destination
            ]
        ];

        $this->originDestinationInformation[] = $segment;

        if ($returnDate) {
            $returnSegment = [
                'RPH' => (string)(count($this->originDestinationInformation) + 1),
                'DepartureDateTime' => $returnDate . 'T00:00:00',
                'OriginLocation' => [
                    'LocationCode' => $destination
                ],
                'DestinationLocation' => [
                    'LocationCode' => $origin
                ]
            ];
            $this->originDestinationInformation[] = $returnSegment;
        }

        return $this;
    }

    public function setTravelPreferences(
        ?array $vendorPrefs = null,
        ?array $cabinPrefs = null,
        ?bool $directFlights = null
    ): self {
        $prefs = [];

        if ($vendorPrefs) {
            $prefs['VendorPref'] = array_map(function ($code) {
                return ['Code' => $code];
            }, $vendorPrefs);
        }

        if ($cabinPrefs) {
            $prefs['CabinPref'] = array_map(function ($cabin) {
                return ['Cabin' => $cabin];
            }, $cabinPrefs);
        }

        // if ($directFlights !== null) {
        //     $prefs['DirectFlightsOnly'] = $directFlights;
        // }

        $this->travelPreferences = $prefs;
        return $this;
    }

    public function addTraveler(string $type, int $quantity): self
    {
        $this->travelerInfoSummary['AirTravelerAvail'][] = [
            'PassengerTypeQuantity' => [
                [
                    'Code' => $type,
                    'Quantity' => $quantity
                ]
            ]
        ];
        return $this;
    }


    public function addCurrencyOverride(string $currency): self
    {
        $this->travelerInfoSummary['PriceRequestInformation'] = [
            'CurrencyCode' => $currency
        ];
        return $this;
    }

    public function setNumTrips(int $num): self
    {
        $this->numTrips = $num;
        return $this;
    }

    public function setDataSourcePreferences(
        bool $enableNDC = true,
        bool $enableATPCO = false,
        bool $enableLCC = false,
        bool $preferNDCSourceOnTie = true
    ): self {
        $this->enableNDC = $enableNDC;
        $this->enableATPCO = $enableATPCO;
        $this->enableLCC = $enableLCC;
        $this->preferNDCSourceOnTie = $preferNDCSourceOnTie;
        return $this;
    }

    public function validate(): bool
    {
        if (empty($this->pseudoCityCode)) {
            throw new SabreApiException('Pseudo City Code is required');
        }

        if (empty($this->originDestinationInformation)) {
            throw new SabreApiException('At least one origin-destination pair is required');
        }

        if (empty($this->travelerInfoSummary)) {
            throw new SabreApiException('At least one traveler is required');
        }

        return true;
    }

    public function toArray(): array
    {
        $this->validate();

        return [
            'OTA_AirLowFareSearchRQ' => [
                'Version' => '1',
                'POS' => [
                    'Source' => [
                        [
                            'PseudoCityCode' => $this->pseudoCityCode,
                            'RequestorID' => [
                                'Type' => '1',
                                'ID' => '1',
                                'CompanyName' => [
                                    'Code' => 'TN'
                                ]
                            ]
                        ]
                    ]
                ],
                'OriginDestinationInformation' => $this->originDestinationInformation,
                'TravelPreferences' => array_merge(
                    $this->travelPreferences,
                    [
                        'ETicketDesired' => true,
                        'TPA_Extensions' => [
                            'NumTrips' => [
                                'Number' => $this->numTrips
                            ],
                            'DataSources' => [
                                'NDC' => $this->enableNDC ? 'Enable' : 'Disable',
                                'ATPCO' => $this->enableATPCO ? 'Enable' : 'Disable',
                                'LCC' => $this->enableLCC ? 'Enable' : 'Disable'
                            ],
                            'PreferNDCSourceOnTie' => [
                                'Value' => $this->preferNDCSourceOnTie
                            ]
                        ]
                    ]
                ),
                'TravelerInfoSummary' => $this->travelerInfoSummary,
                'TPA_Extensions' => [
                    'IntelliSellTransaction' => [
                        'RequestType' => [
                            'Name' => '200ITINS'
                        ]
                    ]
                ]
            ]
        ];
    }
}
