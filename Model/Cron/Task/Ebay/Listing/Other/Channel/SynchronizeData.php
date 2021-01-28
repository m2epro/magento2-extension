<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData
 */
class SynchronizeData extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/listing/other/channel/synchronize_data';

    /**
     * @var int (in seconds)
     */
    protected $interval = 86400;

    const LOCK_ITEM_PREFIX = 'synchronization_ebay_other_listings_update';

    //####################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER_LISTINGS);

        return $synchronizationLog;
    }

    //########################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    protected function performActions()
    {
        /** @var $accountsCollection \Ess\M2ePro\Model\ResourceModel\Account\Collection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Account'
        )->getCollection();
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
                    $this->executeUpdateInventoryDataAccount($account);
                } catch (\Exception $exception) {
                    $message = $this->getHelper('Module\Translation')->__(
                        'The "Update Unmanaged Listings" Action for eBay Account "%account%" was completed with error.',
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

    protected function executeUpdateInventoryDataAccount(\Ess\M2ePro\Model\Account $account)
    {
        $sinceTime = $account->getChildObject()->getData('other_listings_last_synchronization');

        if (empty($sinceTime)) {
            $marketplaceCollection = $this->parentFactory->getObject(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'Marketplace'
            )->getCollection();
            $marketplaceCollection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
            $marketplace = $marketplaceCollection->getFirstItem();

            if (!$marketplace->getId()) {
                $marketplace = \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_US;
            }

            $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getCustomConnector(
                'Cron_Task_Ebay_Listing_Other_Channel_SynchronizeData_Requester',
                [],
                $marketplace,
                $account
            );
            $dispatcherObject->process($connectorObj);
            return;
        }

        $sinceTime = $this->prepareSinceTime($sinceTime);
        $changes = $this->getChangesByAccount($account, $sinceTime);

        /** @var $updatingModel \Ess\M2ePro\Model\Ebay\Listing\Other\Updating */
        $updatingModel = $this->modelFactory->getObject('Ebay_Listing_Other_Updating');
        $updatingModel->initialize($account);
        $updatingModel->processResponseData($changes);
    }

    //########################################

    protected function getChangesByAccount(\Ess\M2ePro\Model\Account $account, $sinceTime)
    {
        $nextSinceTime = new \DateTime($sinceTime, new \DateTimeZone('UTC'));

        $operationHistory = $this->getOperationHistory()->getParentObject('cron_runner');
        if ($operationHistory !== null) {
            $toTime = new \DateTime($operationHistory->getData('start_date'), new \DateTimeZone('UTC'));
        } else {
            $toTime = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        $toTime->modify('-1 hour');

        if ((int)$nextSinceTime->format('U') >= (int)$toTime->format('U')) {
            $nextSinceTime = $toTime;
            $nextSinceTime->modify('-1 minute');
        }

        $response = $this->receiveChangesFromEbay(
            $account,
            ['since_time'=>$nextSinceTime->format('Y-m-d H:i:s'), 'to_time' => $toTime->format('Y-m-d H:i:s')]
        );

        if ($response) {
            return (array)$response;
        }

        $previousSinceTime = $nextSinceTime;

        $nextSinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $nextSinceTime->modify('-1 day');

        if ($previousSinceTime->format('U') < $nextSinceTime->format('U')) {
            $response = $this->receiveChangesFromEbay(
                $account,
                ['since_time'=>$nextSinceTime->format('Y-m-d H:i:s'), 'to_time' => $toTime->format('Y-m-d H:i:s')]
            );

            if ($response) {
                return (array)$response;
            }

            $previousSinceTime = $nextSinceTime;
        }

        $nextSinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $nextSinceTime->modify('-2 hours');

        if ($previousSinceTime->format('U') < $nextSinceTime->format('U')) {
            $response = $this->receiveChangesFromEbay(
                $account,
                ['since_time'=>$nextSinceTime->format('Y-m-d H:i:s'), 'to_time' => $toTime->format('Y-m-d H:i:s')]
            );

            if ($response) {
                return (array)$response;
            }
        }

        return [];
    }

    protected function receiveChangesFromEbay(\Ess\M2ePro\Model\Account $account, array $paramsConnector = [])
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'inventory',
            'get',
            'events',
            $paramsConnector,
            null,
            null,
            $account->getId()
        );

        $dispatcherObj->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        $this->processResponseMessages($connectorObj->getResponseMessages());

        if (!isset($responseData['items']) || !isset($responseData['to_time'])) {
            return null;
        }

        return $responseData;
    }

    protected function processResponseMessages(array $messages)
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($messages);

        foreach ($messagesSet->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType
            );
        }
    }

    //########################################

    protected function prepareSinceTime($sinceTime)
    {
        $minTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $minTime->modify("-1 month");

        if (empty($sinceTime) || strtotime($sinceTime) < (int)$minTime->format('U')) {
            $sinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $sinceTime = $sinceTime->format('Y-m-d H:i:s');
        }

        return $sinceTime;
    }

    protected function isLockedAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $lockItem \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => self::LOCK_ITEM_PREFIX.'_'.$account->getId()
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
