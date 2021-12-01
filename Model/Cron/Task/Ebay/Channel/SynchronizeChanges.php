<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Channel;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges
 */
class SynchronizeChanges extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/channel/synchronize_changes';

    /**
     * @var int (in seconds)
     */
    protected $interval = 300;

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();
        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

        return $synchronizationLog;
    }

    //########################################

    protected function performActions()
    {
        $this->processOrdersChanges();
        $this->processItemsChanges();
    }

    //########################################

    protected function processItemsChanges()
    {
        $itemsProcessor = $this->modelFactory->getObject('Cron_Task_Ebay_Channel_SynchronizeChanges_ItemsProcessor');

        $synchronizationLog = $this->getSynchronizationLog();
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER);

        $itemsProcessor->setSynchronizationLog($synchronizationLog);

        $operationHistory = $this->getOperationHistory()->getParentObject();
        if ($operationHistory !== null) {
            $itemsProcessor->setReceiveChangesToDate($operationHistory->getData('start_date'));
        }

        $itemsProcessor->process();
    }

    protected function processOrdersChanges()
    {
        $ordersProcessor = $this->modelFactory->getObject('Cron_Task_Ebay_Channel_SynchronizeChanges_OrdersProcessor');

        $synchronizationLog = $this->getSynchronizationLog();
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        $ordersProcessor->setSynchronizationLog($synchronizationLog);

        $operationHistory = $this->getOperationHistory()->getParentObject();
        if ($operationHistory !== null) {
            $ordersProcessor->setReceiveOrdersToDate($operationHistory->getData('start_date'));
        }

        $ordersProcessor->process();
    }

    //########################################
}
