<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker;

class ChangeTrackerProcessor
{
    private \Ess\M2ePro\Model\ChangeTracker\Base\InventoryTrackerFactory $inventoryTrackerFactory;
    private \Ess\M2ePro\Model\ChangeTracker\Base\PriceTrackerFactory $priceTrackerFactory;
    private \Ess\M2ePro\Model\ChangeTracker\Base\ChangeHolderFactory $changeHolderFactory;
    private \Ess\M2ePro\Model\ChangeTracker\PartManager $partManager;
    private \Magento\Framework\Event\ManagerInterface $eventDispatcher;

    public function __construct(
        \Ess\M2ePro\Model\ChangeTracker\Base\InventoryTrackerFactory $inventoryTrackerFactory,
        \Ess\M2ePro\Model\ChangeTracker\Base\PriceTrackerFactory $priceTrackerFactory,
        \Ess\M2ePro\Model\ChangeTracker\Base\ChangeHolderFactory $changeHolderFactory,
        \Ess\M2ePro\Model\ChangeTracker\PartManager $partManager,
        \Magento\Framework\Event\ManagerInterface $eventDispatcher
    ) {
        $this->inventoryTrackerFactory = $inventoryTrackerFactory;
        $this->priceTrackerFactory = $priceTrackerFactory;
        $this->changeHolderFactory = $changeHolderFactory;
        $this->partManager = $partManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function process(): void
    {
        $totalPartsCount = $this->partManager->getPartsCount();
        $processedPartNumber = 0;
        foreach ($this->partManager->getListingProductParts() as $part) {
            $processedPartNumber++;
            $this->processPart($part);
            $this->dispatchKeepAliveEvent($totalPartsCount, $processedPartNumber);
        }
    }

    /**
     * @param TrackerConfiguration[] $part
     * @return void
     * @throws \Throwable
     */
    private function processPart(array $part): void
    {
        foreach ($part as $trackerConfiguration) {
            $this->trackInventoryChanges($trackerConfiguration);
            $this->trackPriceChanges($trackerConfiguration);
        }
    }

    private function trackInventoryChanges(TrackerConfiguration $trackerConfiguration): void
    {
        $changeHolder = $this->changeHolderFactory->create();
        $changeHolder->holdChanges(
            $this->inventoryTrackerFactory->createByConfiguration(
                $trackerConfiguration
            )
        );
    }

    private function trackPriceChanges(TrackerConfiguration $trackerConfiguration): void
    {
        $changeHolder = $this->changeHolderFactory->create();
        $changeHolder->holdChanges(
            $this->priceTrackerFactory->createByConfiguration(
                $trackerConfiguration
            )
        );
    }

    private function dispatchKeepAliveEvent(int $totalPartsCount, int $currentPartNumber): void
    {
        $percentage = ceil($currentPartNumber * 100 / $totalPartsCount);

        $this->eventDispatcher->dispatch(
            \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_SET_DETAILS_EVENT_NAME,
            [
                'progress_nick' => \Ess\M2ePro\Model\Cron\Task\Listing\Product\ChangeTracker::NICK,
                'percentage' => $percentage,
                'total' => $totalPartsCount,
            ]
        );
    }
}
