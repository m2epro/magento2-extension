<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Base;

use Ess\M2ePro\Model\ChangeTracker\TrackerConfiguration;

class PriceTrackerFactory implements TrackerFactoryInterface
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createByConfiguration(TrackerConfiguration $trackerConfiguration): TrackerInterface
    {
        switch ($trackerConfiguration->component) {
            case TrackerInterface::CHANNEL_EBAY:
                $class = \Ess\M2ePro\Model\ChangeTracker\Ebay\PriceTracker::class;
                break;
            case TrackerInterface::CHANNEL_AMAZON:
                $class = \Ess\M2ePro\Model\ChangeTracker\Amazon\PriceTracker::class;
                break;
            case TrackerInterface::CHANNEL_WALMART:
                $class = \Ess\M2ePro\Model\ChangeTracker\Walmart\PriceTracker::class;
                break;
            default:
                throw new \RuntimeException('Unknown chanel ' . $trackerConfiguration->component);
        }

        return $this->objectManager->create($class, [
            'channel' => $trackerConfiguration->component,
            'listingProductIdFrom' => $trackerConfiguration->listingProductIdFrom,
            'listingProductIdTo' => $trackerConfiguration->listingProductIdTo,
        ]);
    }
}
