<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel;

use \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\ProcessingRunner as ProcessingRunner;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData
 */
class SynchronizeData extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/listing/other/channel/synchronize_data';

    /**
     * @var int (in seconds)
     */
    protected $interval = 86400;

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
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER_LISTINGS);

        return $synchronizationLog;
    }

    //########################################

    protected function performActions()
    {
        /** @var $accountsCollection \Ess\M2ePro\Model\ResourceModel\Account\Collection */
        $accountsCollection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Account')
            ->getCollection();
        $accountsCollection->addFieldToFilter('other_listings_synchronization', 1);

        $accounts = $accountsCollection->getItems();

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
                    $params = [];
                    if (!$this->isFullItemsDataAlreadyReceived($account)) {
                        $params['full_items_data'] = true;

                        $additionalData = (array)$this->getHelper('Data')->jsonDecode($account->getAdditionalData());
                        $additionalData['is_amazon_other_listings_full_items_data_already_received'] = true;
                        $account->setSettings('additional_data', $additionalData)->save();
                    }

                    $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
                    $connectorObj = $dispatcherObject->getCustomConnector(
                        'Cron_Task_Amazon_Listing_Other_Channel_SynchronizeData_Requester',
                        $params,
                        $account
                    );

                    $dispatcherObject->process($connectorObj);
                } catch (\Exception $exception) {
                    $message = $this->getHelper('Module_Translation')->__(
                        'The "3rd Party Listings" Action for Amazon Account "%account%" was completed with error.',
                        $account->getTitle()
                    );

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }

                $this->getOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            }
        }
    }

    //########################################

    protected function isLockedAccount(\Ess\M2ePro\Model\Account $account)
    {
        $lockItemNick = ProcessingRunner::LOCK_ITEM_PREFIX.'_'.$account->getId();

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

    protected function isFullItemsDataAlreadyReceived(\Ess\M2ePro\Model\Account $account)
    {
        $additionalData = (array)$this->getHelper('Data')->jsonDecode($account->getAdditionalData());
        return !empty($additionalData['is_amazon_other_listings_full_items_data_already_received']);
    }

    //########################################
}
