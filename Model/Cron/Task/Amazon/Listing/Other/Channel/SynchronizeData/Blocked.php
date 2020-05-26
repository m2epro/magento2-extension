<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData;

use \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\Blocked\ProcessingRunner as Runner;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\Blocked
 */
class Blocked extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/listing/other/channel/synchronize_data/blocked';

    /**
     * @var int (in seconds)
     */
    protected $interval = 3600;

    //####################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server_Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
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

            if (!$this->isLockedAccount($account) && !$this->isLockedAccountInterval($account)) {
                $this->getOperationHistory()->addTimePoint(
                    __METHOD__.'process'.$account->getId(),
                    'Process Account '.$account->getTitle()
                );

                $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
                $connectorObj = $dispatcherObject->getCustomConnector(
                    'Cron_Task_Amazon_Listing_Other_Channel_SynchronizeData_Blocked_Requester',
                    [],
                    $account
                );

                $dispatcherObject->process($connectorObj);

                $this->getOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            }
        }
    }

    //########################################

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

    protected function isLockedAccountInterval(\Ess\M2ePro\Model\Account $account)
    {
        if ($this->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_USER ||
            $this->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER) {
            return false;
        }

        $additionalData = $this->getHelper('Data')->jsonDecode($account->getAdditionalData());
        if (!empty($additionalData['last_other_listing_products_synchronization'])) {
            return (strtotime($additionalData['last_other_listing_products_synchronization'])
                    + 86400) > $this->getHelper('Data')->getCurrentGmtDate(true);
        }

        return false;
    }

    //########################################
}
