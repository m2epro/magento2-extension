<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Partial\Runner
{
    public const LOCK_ITEM_PREFIX = 'synchronization_amazon_listings_products_update_afnQty';

    private \Ess\M2ePro\Model\Amazon\Listing\Product\EventDispatcher $listingProductEventDispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Listing\Product\EventDispatcher $listingProductEventDispatcher,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($parentFactory, $activeRecordFactory, $helperData, $helperFactory, $modelFactory);

        $this->listingProductEventDispatcher = $listingProductEventDispatcher;
    }

    public function processSuccess()
    {
        $result = parent::processSuccess();

        $params = $this->getParams();
        $this->listingProductEventDispatcher->dispatchEventFbaProductSourceItemsUpdated(
            $params['responser_params']['merchant_id']
        );

        return $result;
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject(
            'Lock_Item_Manager',
            ['nick' => $this->getLockNick()]
        );
        $lockItemManager->create();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account',
            $params['account_id']
        );

        $account->addProcessingLock(null, $this->getProcessingObject()->getId());
        $account->addProcessingLock('synchronization', $this->getProcessingObject()->getId());
        $account->addProcessingLock('synchronization_amazon', $this->getProcessingObject()->getId());
        $account->addProcessingLock(self::LOCK_ITEM_PREFIX, $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItem */
        $lockItem = $this->modelFactory->getObject(
            'Lock_Item_Manager',
            ['nick' => $this->getLockNick()]
        );
        $lockItem->remove();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account',
            $params['account_id']
        );

        $account->deleteProcessingLocks(null, $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('synchronization', $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('synchronization_amazon', $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks(self::LOCK_ITEM_PREFIX, $this->getProcessingObject()->getId());
    }

    private function getLockNick(): string
    {
        $params = $this->getParams();

        return sprintf(
            '%s_%s',
            self::LOCK_ITEM_PREFIX,
            $params['responser_params']['account_id']
        );
    }
}
