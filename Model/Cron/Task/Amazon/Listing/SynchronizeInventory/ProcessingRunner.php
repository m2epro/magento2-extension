<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\SynchronizeInventory;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\SynchronizeInventory\ProcessingRunner
 */
class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Partial\Runner
{
    const LOCK_ITEM_PREFIX = 'synchronization_amazon_listing_inventory';

    /** @var \Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon\BlockedProductsHandler */
    protected $blockedProductsHandler;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    //##################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Listing\SynchronizeInventory\Amazon\BlockedProductsHandler $blockedProductsHandler,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        parent::__construct($parentFactory, $activeRecordFactory, $helperFactory, $modelFactory);

        $this->blockedProductsHandler = $blockedProductsHandler;
        $this->resourceConnection     = $resourceConnection;
    }

    //##################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        /** @var $lockItemManager \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', ['nick' => self::LOCK_ITEM_PREFIX]);
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

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        /** @var $lockItem \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItem = $this->modelFactory->getObject('Lock_Item_Manager', ['nick' => self::LOCK_ITEM_PREFIX]);
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

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    protected function afterLastDataPartProcessed()
    {
        parent::afterLastDataPartProcessed();

        $responserParams = $this->getResponserParams();
        $this->blockedProductsHandler->setResponserParams($responserParams)->handle();

        $this->resourceConnection->getConnection()->truncateTable(
            $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_amazon_inventory_sku')
        );
    }

    //##################################
}
