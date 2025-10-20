<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker;

class TrackerRepository
{
    private const TRACKERS_BY_CHANNEL = [
        \Ess\M2ePro\Model\ChangeTracker\TrackerInterface::CHANNEL_EBAY => [
            \Ess\M2ePro\Model\ChangeTracker\Ebay\PriceTracker::class,
            \Ess\M2ePro\Model\ChangeTracker\Ebay\BestOfferTracker::class,
            \Ess\M2ePro\Model\ChangeTracker\Ebay\InventoryTracker::class,
        ],
        \Ess\M2ePro\Model\ChangeTracker\TrackerInterface::CHANNEL_AMAZON => [
            \Ess\M2ePro\Model\ChangeTracker\Amazon\PriceTracker::class,
            \Ess\M2ePro\Model\ChangeTracker\Amazon\InventoryTracker::class,
        ],
        \Ess\M2ePro\Model\ChangeTracker\TrackerInterface::CHANNEL_WALMART => [
            \Ess\M2ePro\Model\ChangeTracker\Walmart\PriceTracker::class,
            \Ess\M2ePro\Model\ChangeTracker\Walmart\InventoryTracker::class,
        ],
    ];

    /**
     * @return string[]
     */
    public function getTrackersByChannel(string $channel): array
    {
        if (!$this->isValidChannel($channel)) {
            throw new \Ess\M2ePro\Model\ChangeTracker\Exceptions\ChangeTrackerException(
                sprintf('Unknown change tracker channel "%s"', $channel)
            );
        }

        return self::TRACKERS_BY_CHANNEL[$channel];
    }

    private function isValidChannel(string $channel): bool
    {
        return in_array($channel, array_keys(self::TRACKERS_BY_CHANNEL), true);
    }
}
