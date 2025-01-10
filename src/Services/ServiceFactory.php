<?php

namespace Santosdave\Sabre\Services;

use Santosdave\Sabre\Contracts\SabreAuthenticatable;
use Santosdave\Sabre\Contracts\Services\AirAvailabilityServiceInterface;
use Santosdave\Sabre\Contracts\Services\AirBookingServiceInterface;
use Santosdave\Sabre\Contracts\Services\AirIntelligenceServiceInterface;
use Santosdave\Sabre\Contracts\Services\AirPricingServiceInterface;
use Santosdave\Sabre\Contracts\Services\AirShoppingServiceInterface;
use Santosdave\Sabre\Contracts\Services\AncillaryServiceInterface;
use Santosdave\Sabre\Contracts\Services\CacheShoppingServiceInterface;
use Santosdave\Sabre\Contracts\Services\ExchangeServiceInterface;
use Santosdave\Sabre\Contracts\Services\OrderManagementServiceInterface;
use Santosdave\Sabre\Contracts\Services\QueueServiceInterface;
use Santosdave\Sabre\Contracts\Services\SeatServiceInterface;
use Santosdave\Sabre\Contracts\Services\UtilityServiceInterface;
use Santosdave\Sabre\Exceptions\SabreApiException;

class ServiceFactory
{
    public const REST = 'rest';
    public const SOAP = 'soap';

    public function __construct(
        private SabreAuthenticatable $auth,
        private string $environment = 'cert'
    ) {}
    private const SERVICE_MAPPINGS = [
        'shopping' => [
            self::REST => Rest\Air\ShoppingService::class,
            self::SOAP => Soap\Air\ShoppingService::class,
        ],
        'availability' => [
            self::REST => Rest\Air\AvailabilityService::class,
            self::SOAP => Soap\Air\AvailabilityService::class,
        ],
        'orderManagement' => [
            self::REST => Rest\Air\OrderManagementService::class,
        ],
        'booking' => [
            self::REST => Rest\Air\BookingService::class,
            self::SOAP => Soap\Air\BookingService::class,
        ],
        'utility' => [
            self::REST => Rest\UtilityService::class,
            self::SOAP => Soap\UtilityService::class,
        ],
        'intelligence' => [
            self::REST => Rest\Air\IntelligenceService::class,
            self::SOAP => Soap\Air\IntelligenceService::class,
        ],
        'cacheShopping' => [
            self::REST => Rest\Air\CacheShoppingService::class,
        ],
        'pricing' => [
            self::REST => Rest\Air\PricingService::class,
        ],
        'exchange' => [
            self::REST => Rest\Air\ExchangeService::class,
        ],
        'seat' => [
            self::REST => Rest\Air\SeatService::class,
        ],
        'queue' => [
            self::REST => Rest\QueueService::class,
            self::SOAP => Soap\QueueService::class,
        ],
        'ancillary' => [
            self::REST => Rest\Air\AncillaryService::class,
        ],
    ];

    public function createAirShoppingService(string $type = self::REST): AirShoppingServiceInterface
    {
        $serviceClass = self::SERVICE_MAPPINGS['shopping'][$type] ?? null;

        if (!$serviceClass) {
            throw new \InvalidArgumentException("Invalid service type: {$type}");
        }

        return new $serviceClass($this->auth, $this->environment);
    }

    private function createService(string $serviceKey, string $type): mixed
    {
        $serviceClass = self::SERVICE_MAPPINGS[$serviceKey][$type] ?? null;

        if (!$serviceClass) {
            if (isset(self::SERVICE_MAPPINGS[$serviceKey]) && count(self::SERVICE_MAPPINGS[$serviceKey]) === 1) {
                throw new SabreApiException("This service is only available via " . array_key_first(self::SERVICE_MAPPINGS[$serviceKey]) . " API");
            }
            throw new \InvalidArgumentException("Invalid service type: {$type}");
        }

        return new $serviceClass($this->auth, $this->environment);
    }

    public function createAirAvailabilityService(string $type = self::REST): AirAvailabilityServiceInterface
    {
        return $this->createService('availability', $type);
    }

    public function createOrderManagementService(string $type = self::REST): OrderManagementServiceInterface
    {
        return $this->createService('orderManagement', $type);
    }

    public function createAirBookingService(string $type = self::REST): AirBookingServiceInterface
    {
        return $this->createService('booking', $type);
    }

    public function createUtilityService(string $type = self::REST): UtilityServiceInterface
    {
        return $this->createService('utility', $type);
    }

    public function createAirIntelligenceService(string $type = self::REST): AirIntelligenceServiceInterface
    {
        return $this->createService('intelligence', $type);
    }

    public function createCacheShoppingService(string $type = self::REST): CacheShoppingServiceInterface
    {
        return $this->createService('cacheShopping', $type);
    }

    public function createAirPricingService(string $type = self::REST): AirPricingServiceInterface
    {
        return $this->createService('pricing', $type);
    }

    public function createExchangeService(string $type = self::REST): ExchangeServiceInterface
    {
        return $this->createService('exchange', $type);
    }

    public function createSeatService(string $type = self::REST): SeatServiceInterface
    {
        return $this->createService('seat', $type);
    }

    public function createQueueService(string $type = self::REST): QueueServiceInterface
    {
        return $this->createService('queue', $type);
    }

    public function createAncillaryService(string $type = self::REST): AncillaryServiceInterface
    {
        return $this->createService('ancillary', $type);
    }


    // Add more factory methods for other services
}