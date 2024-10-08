<?php

namespace Ess\M2ePro\Model\Walmart\Marketplace;

class Synchronization extends \Ess\M2ePro\Model\AbstractModel
{
    private const LOCK_ITEM_MAX_ALLOWED_INACTIVE_TIME = 1800; // 30 min

    private \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $connectorDispatcher;
    private \Ess\M2ePro\Model\Walmart\Dictionary\MarketplaceFactory $marketplaceDictionaryFactory;
    private \Ess\M2ePro\Model\Walmart\Dictionary\Marketplace\Repository $marketplaceDictionaryRepository;
    private \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory;
    private \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent;
    private ?\Ess\M2ePro\Model\Marketplace $marketplace = null;
    private ?\Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager = null;
    private ?\Ess\M2ePro\Model\Lock\Item\Progress $progressManager = null;
    private ?\Ess\M2ePro\Model\Synchronization\Log $synchronizationLog = null;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $connectorDispatcher,
        \Ess\M2ePro\Model\Walmart\Dictionary\MarketplaceFactory $marketplaceDictionaryFactory,
        \Ess\M2ePro\Model\Walmart\Dictionary\Marketplace\Repository $marketplaceDictionaryRepository,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->connectorDispatcher = $connectorDispatcher;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->cachePermanent = $cachePermanent;
        $this->marketplaceDictionaryFactory = $marketplaceDictionaryFactory;
        $this->marketplaceDictionaryRepository = $marketplaceDictionaryRepository;
    }

    public function isMarketplaceAllowed(\Ess\M2ePro\Model\Marketplace $marketplace): bool
    {
        return $marketplace->getChildObject()
                           ->isCanada();
    }

    public function setMarketplace(\Ess\M2ePro\Model\Marketplace $marketplace): self
    {
        if (!$this->isMarketplaceAllowed($marketplace)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace not allowed for synchronization.');
        }

        $this->marketplace = $marketplace;

        return $this;
    }

    public function isLocked(): bool
    {
        if (!$this->getLockItemManager()->isExist()) {
            return false;
        }

        if ($this->getLockItemManager()->isInactiveMoreThanSeconds(self::LOCK_ITEM_MAX_ALLOWED_INACTIVE_TIME)) {
            $this->getLockItemManager()->remove();

            return false;
        }

        return true;
    }

    public function process(): void
    {
        $this->getLockItemManager()->create();

        $this->getProgressManager()->setPercentage(0);

        $this->processDetails();

        $this->cachePermanent->removeTagValues('marketplace');

        $this->getProgressManager()->setPercentage(100);

        $this->getLockItemManager()->remove();
    }

    protected function processDetails(): void
    {
        $connectorObj = $this->connectorDispatcher->getVirtualConnector(
            'marketplace',
            'get',
            'info',
            [
                'include_details' => true,
                'marketplace' => $this->marketplace->getNativeId(),
            ],
            'info',
            null
        );

        $this->connectorDispatcher->process($connectorObj);
        $details = $connectorObj->getResponseData();

        if ($details === null) {
            return;
        }

        $this->marketplaceDictionaryRepository->removeByMarketplace(
            (int)$this->marketplace->getId()
        );

        $lastUpdateDate = \Ess\M2ePro\Helper\Date::createDateGmt($details['last_update']);
        $marketplaceDictionary = $this->marketplaceDictionaryFactory->createWithoutProductTypes(
            (int)$this->marketplace->getId(),
            $lastUpdateDate,
            $lastUpdateDate
        );

        $this->marketplaceDictionaryRepository->create($marketplaceDictionary);
    }

    public function getLockItemManager(): \Ess\M2ePro\Model\Lock\Item\Manager
    {
        if ($this->lockItemManager !== null) {
            return $this->lockItemManager;
        }

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK,
        ]);

        return $this->lockItemManager = $lockItemManager;
    }

    public function getProgressManager(): \Ess\M2ePro\Model\Lock\Item\Progress
    {
        if ($this->progressManager !== null) {
            return $this->progressManager;
        }

        /** @var \Ess\M2ePro\Model\Lock\Item\Progress $progressManager */
        $progressManager = $this->modelFactory->getObject('Lock_Item_Progress', [
            'lockItemManager' => $this->getLockItemManager(),
            'progressNick' => $this->marketplace->getTitle() . ' Marketplace',
        ]);

        return $this->progressManager = $progressManager;
    }

    public function getLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        /** @var \Ess\M2ePro\Model\Synchronization\Log $synchronizationLog */
        $synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_MARKETPLACES);

        return $this->synchronizationLog = $synchronizationLog;
    }
}
