<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Listing\Product;

class ChangeTracker extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'listing/product/change_tracker';

    private \Ess\M2ePro\Helper\Module\ChangeTracker $changeTrackerHelper;
    private \Ess\M2ePro\Model\ChangeTracker\ChangeTrackerProcessor $changeTrackerProcessor;

    public function __construct(
        \Ess\M2ePro\Model\ChangeTracker\ChangeTrackerProcessor $changeTrackerProcessor,
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ChangeTracker\Base\InventoryTrackerFactory $inventoryTrackerFactory,
        \Ess\M2ePro\Model\ChangeTracker\Base\PriceTrackerFactory $priceTrackerFactory,
        \Ess\M2ePro\Model\ChangeTracker\Base\ChangeHolderFactory $changeHolderFactory,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\Profiler $profiler,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger,
        \Ess\M2ePro\Helper\Module\ChangeTracker $changeTrackerHelper,
        \Ess\M2ePro\Model\ChangeTracker\PartManager $chunkManager
    ) {
        parent::__construct(
            $cronManager,
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
        $this->inventoryTrackerFactory = $inventoryTrackerFactory;
        $this->priceTrackerFactory = $priceTrackerFactory;
        $this->changeHolderFactory = $changeHolderFactory;
        $this->profiler = $profiler;
        $this->logger = $logger;
        $this->changeTrackerHelper = $changeTrackerHelper;
        $this->chunkManager = $chunkManager;
        $this->changeTrackerProcessor = $changeTrackerProcessor;
    }

    /**
     * @return int
     */
    public function getInterval(): int
    {
        return $this->changeTrackerHelper->getInterval();
    }

    public function isPossibleToRun()
    {
        if (!$this->changeTrackerHelper->isEnabled()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    /**
     * @throws \Throwable
     */
    protected function performActions(): void
    {
        $this->changeTrackerProcessor->process();
    }
}
