<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

class PriceConditionFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $channel): AbstractPriceCondition
    {
        $arguments = ['channel' => $channel];

        if ($channel === \Ess\M2ePro\Model\ChangeTracker\TrackerInterface::CHANNEL_EBAY) {
            return $this->objectManager->create(Ebay::class, $arguments);
        }

        if ($channel === \Ess\M2ePro\Model\ChangeTracker\TrackerInterface::CHANNEL_AMAZON) {
            return $this->objectManager->create(Amazon::class, $arguments);
        }

        if ($channel === \Ess\M2ePro\Model\ChangeTracker\TrackerInterface::CHANNEL_WALMART) {
            return $this->objectManager->create(Walmart::class, $arguments);
        }

        throw new \RuntimeException('Undefined channel name ' . $channel);
    }
}
