<?php

namespace Santosdave\Sabre;

use Psr\Log\LoggerInterface;
use Santosdave\Sabre\Services\ServiceFactory;
use Santosdave\Sabre\Contracts\Services\AirShoppingServiceInterface;
use Santosdave\Sabre\Contracts\Services\AirBookingServiceInterface;
use Santosdave\Sabre\Contracts\Services\AirAvailabilityServiceInterface;
use Santosdave\Sabre\Contracts\Services\UtilityServiceInterface;

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

    public function utility(string $type = ServiceFactory::REST): UtilityServiceInterface
    {
        $this->logger->debug('Creating utility service', ['type' => $type]);
        return $this->serviceFactory->createUtilityService($type);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}