<?php

namespace Ess\M2ePro\Model\Cron\Task\Listing\Product;

class ChangeTracker extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'listing/product/change_tracker';

    /** @var \Ess\M2ePro\Model\ChangeTracker\Base\TrackerInterface[] */
    private $builders = [];
    /** @var \Ess\M2ePro\Model\ChangeTracker\Base\InventoryTrackerFactory */
    private $inventoryTrackerFactory;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Base\ChangeHolderFactory */
    private $changeHolderFactory;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Base\PriceTrackerFactory */
    private $priceTrackerFactory;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\Profiler */
    private $profiler;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger */
    private $logger;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection */
    private $accountCollection;
    /** @var \Ess\M2ePro\Helper\Module\ChangeTracker */
    private $changeTrackerHelper;

    public function __construct(
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
        \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountCollection,
        \Ess\M2ePro\Helper\Module\ChangeTracker $changeTrackerHelper
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
        $this->accountCollection = $accountCollection;
        $this->changeTrackerHelper = $changeTrackerHelper;
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
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Throwable
     */
    protected function performActions(): void
    {
        $this->profiler->start();
        $this->initBuilders([
            $this->priceTrackerFactory,
            $this->inventoryTrackerFactory,
        ]);
        $this->profiler->stop();

        $this->logger->info('Prepare builders. ' . $this->profiler->logString());

        foreach ($this->builders as $builder) {
            $holder = $this->changeHolderFactory->create();
            $holder->holdChanges($builder);
        }
    }

    /**
     * @param \Ess\M2ePro\Model\ChangeTracker\Base\TrackerFactoryInterface[] $factories
     *
     * @return void
     */
    private function initBuilders(array $factories): void
    {
        foreach ($this->getActiveChannels() as $channel) {
            foreach ($factories as $factory) {
                $this->builders[] = $factory->create($channel);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getActiveChannels(): array
    {
        $collection = $this->accountCollection;
        $select = $collection->getSelect();
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->columns(['channel' => 'component_mode']);
        $select->distinct();

        return $collection->getColumnValues('channel');
    }
}
