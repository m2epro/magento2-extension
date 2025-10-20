<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker;

class TrackerFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $trackerClassName, TrackerConfiguration $trackerConfiguration): TrackerInterface
    {
        return $this->objectManager->create($trackerClassName, [
            'configuration' => $trackerConfiguration,
        ]);
    }
}
