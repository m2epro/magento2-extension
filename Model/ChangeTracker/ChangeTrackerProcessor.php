<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker;

class ChangeTrackerProcessor
{
    private TrackerRepository $trackerRepository;
    private TrackerFactory $trackerFactory;
    private ChangeHolderFactory $changeHolderFactory;
    private PartManager $partManager;
    private \Magento\Framework\Event\ManagerInterface $eventDispatcher;

    public function __construct(
        TrackerRepository $trackerRepository,
        TrackerFactory $trackerFactory,
        ChangeHolderFactory $changeHolderFactory,
        PartManager $partManager,
        \Magento\Framework\Event\ManagerInterface $eventDispatcher
    ) {
        $this->trackerRepository = $trackerRepository;
        $this->trackerFactory = $trackerFactory;
        $this->changeHolderFactory = $changeHolderFactory;
        $this->partManager = $partManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws \Throwable
     */
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
            $trackers = $this->trackerRepository->getTrackersByChannel($trackerConfiguration->channel);
            foreach ($trackers as $trackerClassName) {
                $tracker = $this->trackerFactory
                    ->create($trackerClassName, $trackerConfiguration);

                $this->changeHolderFactory
                    ->create()
                    ->holdChanges($tracker);
            }
        }
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
