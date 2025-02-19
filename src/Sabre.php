<?php

namespace Santosdave\SabreWrapper;

use Psr\Log\LoggerInterface;
use Santosdave\SabreWrapper\Services\ServiceFactory;
use Santosdave\SabreWrapper\Contracts\Services\AirShoppingServiceInterface;
use Santosdave\SabreWrapper\Contracts\Services\AirBookingServiceInterface;
use Santosdave\SabreWrapper\Contracts\Services\AirAvailabilityServiceInterface;
use Santosdave\SabreWrapper\Contracts\Services\AirIntelligenceServiceInterface;
use Santosdave\SabreWrapper\Contracts\Services\AirPricingServiceInterface;
use Santosdave\SabreWrapper\Contracts\Services\CacheShoppingServiceInterface;
use Santosdave\SabreWrapper\Contracts\Services\OrderManagementServiceInterface;
use Santosdave\SabreWrapper\Contracts\Services\UtilityServiceInterface;

class Sabre
{
    public function __construct(
        private ServiceFactory $serviceFactory,
        private LoggerInterface $logger
    ) {}

    public function shopping(string $type = ServiceFactory::REST): AirShoppingServiceInterface
    {
        $this->logger->debug('Creating shopping service', ['type' => $type]);
        return $this->serviceFactory->createAirShoppingService($type);
    }


    public function pricing(string $type = ServiceFactory::REST): AirPricingServiceInterface
    {
        $this->logger->debug('Creating pricing service', ['type' => $type]);
        return $this->serviceFactory->createAirPricingService($type);
    }

    public function order(string $type = ServiceFactory::REST): OrderManagementServiceInterface
    {
        $this->logger->debug('Creating order service', ['type' => $type]);
        return $this->serviceFactory->createOrderManagementService($type);
    }

    public function booking(string $type = ServiceFactory::REST): AirBookingServiceInterface
    {
        $this->logger->debug('Creating booking service', ['type' => $type]);
        return $this->serviceFactory->createAirBookingService($type);
    }

    public function availability(string $type = ServiceFactory::REST): AirAvailabilityServiceInterface
    {
        $this->logger->debug('Creating availability service', ['type' => $type]);
        return $this->serviceFactory->createAirAvailabilityService($type);
    }

    public function cacheShopping(string $type = ServiceFactory::REST): CacheShoppingServiceInterface
    {
        $this->logger->debug('Creating cache shopping service', ['type' => $type]);
        return $this->serviceFactory->createCacheShoppingService($type);
    }


    public function utility(string $type = ServiceFactory::REST): UtilityServiceInterface
    {
        $this->logger->debug('Creating utility service', ['type' => $type]);
        return $this->serviceFactory->createUtilityService($type);
    }
    public function intelligence(string $type = ServiceFactory::REST): AirIntelligenceServiceInterface
    {
        $this->logger->debug('Creating air intelligence service', ['type' => $type]);
        return $this->serviceFactory->createAirIntelligenceService($type);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
