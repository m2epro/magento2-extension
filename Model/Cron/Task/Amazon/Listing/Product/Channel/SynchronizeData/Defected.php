<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData;

use \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Defected\ProcessingRunner as Runner;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Defected
 */
class Defected extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/listing/product/channel/synchronize_data/defected';

    /**
     * @var int (in seconds)
     */
    protected $interval = 259200;

    //####################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS);

        return $synchronizationLog;
    }

    //########################################

    protected function performActions()
    {
        $accounts = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Account')
            ->getCollection()->getItems();

        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {

            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');

            if (!$this->isLockedAccount($account)) {
                $this->getOperationHistory()->addTimePoint(
                    __METHOD__.'process'.$account->getId(),
                    'Process Account '.$account->getTitle()
                );

                try {
                    $this->processAccount($account);
                } catch (\Exception $exception) {
                    $message = 'The "Update Defected Listings Products" Action for Amazon Account "%account%"';
                    $message .= ' was completed with error.';
                    $message = $this->getHelper('Module\Translation')->__($message, $account->getTitle());

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }

                $this->getOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            }
        }
    }

    //########################################

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel */
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $collection->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Amazon::NICK);
        $collection->addFieldToFilter('account_id', (int)$account->getId());

        if ($collection->getSize()) {

            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getCustomConnector(
                'Cron_Task_Amazon_Listing_Product_Channel_SynchronizeData_Defected_Requester',
                [],
                $account
            );
            $dispatcherObject->process($connectorObj);
        }
    }

    protected function isLockedAccount(\Ess\M2ePro\Model\Account $account)
    {
        $lockItemNick = Runner::LOCK_ITEM_PREFIX.'_'.$account->getId();

        /** @var $lockItemManager \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => $lockItemNick
        ]);
        if (!$lockItemManager->isExist()) {
            return false;
        }

        if ($lockItemManager->isInactiveMoreThanSeconds(\Ess\M2ePro\Model\Processing\Runner::MAX_LIFETIME)) {
            $lockItemManager->remove();
            return false;
        }

        return true;
    }

    //########################################
}
