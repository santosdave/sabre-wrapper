# NDC Implementation Examples and Configurations

## Common Scenarios

### 1. Multi-Passenger Booking with Mixed Passenger Types

```php
use Santosdave\SabreWrapper\Facades\Sabre;

class MultiPassengerBookingService
{
    public function bookForFamily(array $passengers, array $flightDetails)
    {
        // 1. Search with multiple passenger types
        $request = new BargainFinderMaxRequest();
        $request->addOriginDestination(
            $flightDetails['origin'],
            $flightDetails['destination'],
            $flightDetails['departureDate']
        );

        // Add each passenger type
        foreach ($passengers as $type => $count) {
            $request->addTraveler($type, $count); // e.g., 'ADT' => 2, 'CHD' => 1, 'INF' => 1
        }

        $searchResults = Sabre::shopping()->bargainFinderMax($request);

        // 2. Price verification for all passengers
        $priceRequest = new OfferPriceRequest();
        $priceRequest->addOfferItem($searchResults->getSelectedOfferId());

        // Add each passenger
        foreach ($passengers as $type => $details) {
            $priceRequest->addPassenger($details['id'], $type);
        }

        $priceResponse = Sabre::pricing()->priceOffer($priceRequest);

        // 3. Create order with all passengers
        $orderRequest = new OrderCreateRequest();
        $orderRequest->setOffer(
            $priceResponse->getOfferId(),
            [$priceResponse->getOfferItemId()]
        );

        // Add all passengers with their details
        foreach ($passengers as $passenger) {
            $orderRequest->addPassenger(
                $passenger['id'],
                $passenger['type'],
                $passenger['givenName'],
                $passenger['surname'],
                $passenger['birthDate'],
                'CI-1'  // All sharing same contact info
            );
        }

        // Add family contact info
        $orderRequest->addContactInfo(
            'CI-1',
            [$passengers[0]['email']],  // Primary passenger email
            [$passengers[0]['phone']]   // Primary passenger phone
        );

        return Sabre::order()->createOrder($orderRequest);
    }
}
```

### 2. Round Trip with Different Carriers

```php
class RoundTripService
{
    public function bookRoundTrip(array $tripDetails)
    {
        $request = new BargainFinderMaxRequest();

        // Add outbound and return
        $request->addOriginDestination(
            $tripDetails['origin'],
            $tripDetails['destination'],
            $tripDetails['departureDate']
        );
        $request->addOriginDestination(
            $tripDetails['destination'],
            $tripDetails['origin'],
            $tripDetails['returnDate']
        );

        // Set preferences for specific carriers
        $request->setTravelPreferences([
            'vendorPrefs' => ['AA', 'BA', 'QF'],  // Preferred carriers
            'cabinPrefs' => ['Y'],                // Economy cabin
            'maxConnections' => 1                  // Max one connection
        ]);

        $shopResponse = Sabre::shopping()->bargainFinderMax($request);

        // Validate interline pricing
        $priceRequest = new OfferPriceRequest();
        $priceRequest->addOfferItem($shopResponse->getSelectedOfferId())
            ->setCreditCard('MC', '545251', 'FDA');

        return Sabre::pricing()->priceOffer($priceRequest);
    }
}
```

### 3. Seat Selection with Ancillaries

```php
class SeatAndAncillaryService
{
    public function addSeatsAndBaggage(string $orderId, array $passengerSeats)
    {
        // 1. First get seat map
        $seatMapRequest = new SeatMapRequest();
        $seatMap = Sabre::seat()->getSeatMap($seatMapRequest);

        // 2. Assign seats for each passenger
        $seatAssignments = [];
        foreach ($passengerSeats as $passenger) {
            $seatRequest = new SeatAssignRequest($orderId);
            $seatRequest->addSeatAssignment(
                $passenger['id'],
                $passenger['segmentId'],
                $passenger['seatNumber'],
                [
                    'window' => true,
                    'extraLegroom' => $passenger['extraLegroom'] ?? false
                ]
            );

            if ($passenger['extraLegroom']) {
                $seatRequest->setPaymentCard(
                    $passenger['payment']['cardNumber'],
                    $passenger['payment']['expirationDate'],
                    $passenger['payment']['cardCode'],
                    $passenger['payment']['cardType'],
                    $passenger['payment']['amount'],
                    'USD'
                );
            }

            $seatAssignments[] = Sabre::seat()->assignSeats($seatRequest);
        }

        // 3. Add baggage for each passenger
        $ancillaryRequest = new AncillaryRequest();
        $ancillaryRequest->setTravelAgencyParty(
            $this->config['pcc'],
            $this->config['agencyId']
        );

        foreach ($passengerSeats as $passenger) {
            $ancillaryRequest->addPassenger(
                $passenger['id'],
                $passenger['type'],
                $passenger['birthDate']
            );
        }

        $ancillaries = Sabre::ancillary()->getAncillaries($ancillaryRequest);

        return [
            'seats' => $seatAssignments,
            'ancillaries' => $ancillaries
        ];
    }
}
```

## NDC Workflow Configurations

### 1. Basic Economy Configuration

```php
// config/sabre-ndc/basic-economy.php
return [
    'shopping' => [
        'cabin_preferences' => ['Y'],
        'max_connections' => 2,
        'data_sources' => [
            'NDC' => 'Enable',
            'ATPCO' => 'Disable',
            'LCC' => 'Enable'
        ],
        'prefer_ndc_source' => true
    ],
    'pricing' => [
        'baggage_pricing' => 'INCLUDED',
        'seat_selection' => 'BASIC',
        'change_fees' => 'NONREFUNDABLE'
    ],
    'fulfillment' => [
        'auto_ticket' => true,
        'payment_types' => ['CC']
    ]
];
```

### 2. Premium Service Configuration

```php
// config/sabre-ndc/premium-service.php
return [
    'shopping' => [
        'cabin_preferences' => ['J', 'C', 'D'], // Business/First
        'max_connections' => 1,
        'data_sources' => [
            'NDC' => 'Enable',
            'ATPCO' => 'Enable',
            'LCC' => 'Disable'
        ],
        'prefer_ndc_source' => true,
        'alliance_preferences' => ['*A', 'OW'] // Star Alliance, Oneworld
    ],
    'pricing' => [
        'baggage_pricing' => 'INCLUDED',
        'seat_selection' => 'PREMIUM',
        'change_fees' => 'REFUNDABLE',
        'lounge_access' => true
    ],
    'fulfillment' => [
        'auto_ticket' => true,
        'payment_types' => ['CC', 'CASH', 'INVOICE'],
        'priority_fulfillment' => true
    ]
];
```

### 3. Corporate Travel Configuration

```php
// config/sabre-ndc/corporate.php
return [
    'shopping' => [
        'corporate_contracts' => true,
        'negotiated_fares' => true,
        'cabin_preferences' => ['Y', 'S', 'C'], // Mix of cabins
        'data_sources' => [
            'NDC' => 'Enable',
            'ATPCO' => 'Enable',
            'LCC' => 'Enable'
        ],
        'preferred_carriers' => [
            'primary' => ['AA', 'UA', 'BA'],
            'secondary' => ['LH', 'AF', 'DL']
        ]
    ],
    'pricing' => [
        'corporate_discounts' => true,
        'baggage_allowance' => 'CORPORATE_POLICY',
        'change_policy' => 'FLEXIBLE',
        'fare_rules' => [
            'advance_purchase' => false,
            'minimum_stay' => false,
            'saturday_night' => false
        ]
    ],
    'fulfillment' => [
        'auto_ticket' => false,
        'approval_required' => true,
        'payment_types' => ['CORPORATE_CC', 'LODGE', 'INVOICE'],
        'documentation' => [
            'include_policy' => true,
            'include_fare_rules' => true,
            'include_baggage_rules' => true
        ]
    ],
    'reporting' => [
        'cost_center_tracking' => true,
        'project_codes' => true,
        'policy_compliance' => true
    ]
];
```

### Implementation Example

```php
class NDCBookingService
{
    private array $config;

    public function __construct(string $profileType = 'basic-economy')
    {
        $this->config = config("sabre-ndc.{$profileType}");
    }

    public function searchFlights(array $criteria): array
    {
        $request = new BargainFinderMaxRequest();

        // Apply configuration
        $request->setTravelPreferences([
            'cabinPrefs' => $this->config['shopping']['cabin_preferences'],
            'maxConnections' => $this->config['shopping']['max_connections'],
            'dataSources' => $this->config['shopping']['data_sources']
        ]);

        if (isset($this->config['shopping']['preferred_carriers'])) {
            $request->setCarrierPreferences(
                $this->config['shopping']['preferred_carriers']['primary'],
                $this->config['shopping']['preferred_carriers']['secondary']
            );
        }

        // Execute search with configured parameters
        return Sabre::shopping()->bargainFinderMax($request);
    }

    public function createOrder(array $orderDetails): OrderCreateResponse
    {
        $request = new OrderCreateRequest();

        // Apply fulfillment configuration
        if ($this->config['fulfillment']['approval_required']) {
            $request->setApprovalWorkflow(true);
        }

        if ($this->config['fulfillment']['auto_ticket']) {
            $request->setAutoTicketing(true);
        }

        // Add payment methods based on configuration
        $request->setAllowedPaymentMethods(
            $this->config['fulfillment']['payment_types']
        );

        return Sabre::order()->createOrder($request);
    }
}
```
