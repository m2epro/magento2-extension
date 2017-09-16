<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\OtherListings;

use Ess\M2ePro\Model\Processing\Runner;

class Update extends AbstractModel
{
    const LOCK_ITEM_PREFIX = 'synchronization_ebay_other_listings_update';

    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/update/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Update';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 0;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 40;
    }

    // ---------------------------------------

    protected function intervalIsEnabled()
    {
        return true;
    }

    //########################################

    protected function performActions()
    {
        $accountsCollection = $this->ebayFactory->getObject('Account')->getCollection();
        $accountsCollection->addFieldToFilter('other_listings_synchronization',
           \Ess\M2ePro\Model\Ebay\Account::OTHER_LISTINGS_SYNCHRONIZATION_YES);

        $accounts = $accountsCollection->getItems();

        if (count($accounts) <= 0) {
            return;
        }

        $iteration = 0;
        $percentsForOneStep = ($this->getPercentsInterval()/2) / count($accounts);

        foreach ($accounts as $account) {

            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
            // M2ePro\TRANSLATIONS
            // The "Update 3rd Party Listings" Action for eBay Account: "%account_title%" is started. Please wait...
            $status = 'The "Update 3rd Party Listings" Action for eBay Account: "%account_title%" is started. ';
            $status .= 'Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );

            if (!$this->isLockedAccount($account)) {

                $this->getActualOperationHistory()->addTimePoint(
                    __METHOD__.'process'.$account->getId(),
                    'Process Account '.$account->getTitle()
                );

                try {

                    $this->executeUpdateInventoryDataAccount($account);

                } catch (\Exception $exception) {

                    $message = $this->getHelper('Module\Translation')->__(
                        'The "Update 3rd Party Listings" Action for eBay Account "%account%" was completed with error.',
                        $account->getTitle()
                    );

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }

                $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            }

            // M2ePro\TRANSLATIONS
            // The "Update 3rd Party Listings" Action for eBay Account: "%account_title%" is finished. Please wait...
            $status = 'The "Update 3rd Party Listings" Action for eBay Account: "%account_title%" is finished. ';
            $status .= 'Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneStep);
            $this->getActualLockItem()->activate();

            $iteration++;
        }
    }

    //########################################

    private function executeUpdateInventoryDataAccount(\Ess\M2ePro\Model\Account $account)
    {
        $sinceTime = $account->getChildObject()->getData('other_listings_last_synchronization');

        if (empty($sinceTime)) {

            $marketplaceCollection = $this->ebayFactory->getObject('Marketplace')->getCollection();
            $marketplaceCollection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
            $marketplace = $marketplaceCollection->getFirstItem();

            if (!$marketplace->getId()) {
                $marketplace = \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_US;
            } else {
                $marketplace = $marketplace->getId();
            }

            $dispatcherObject = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getCustomConnector(
                'Ebay\Synchronization\OtherListings\Update\Requester',
                array(), $marketplace, $account->getId()
            );
            $dispatcherObject->process($connectorObj);
            return;
        }

        $sinceTime = $this->prepareSinceTime($sinceTime);
        $changes = $this->getChangesByAccount($account, $sinceTime);

        /** @var $updatingModel \Ess\M2ePro\Model\Ebay\Listing\Other\Updating */
        $updatingModel = $this->modelFactory->getObject('Ebay\Listing\Other\Updating');
        $updatingModel->initialize($account);
        $updatingModel->processResponseData($changes);
    }

    //########################################

    private function getChangesByAccount(\Ess\M2ePro\Model\Account $account, $sinceTime)
    {
        $nextSinceTime = new \DateTime($sinceTime, new \DateTimeZone('UTC'));

        $operationHistory = $this->getActualOperationHistory()->getParentObject('synchronization');
        if (!is_null($operationHistory)) {
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
            array('since_time'=>$nextSinceTime->format('Y-m-d H:i:s'), 'to_time' => $toTime->format('Y-m-d H:i:s'))
        );

        if ($response) {
            return (array)$response;
        }

        $previousSinceTime = $nextSinceTime;

        $nextSinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $nextSinceTime->modify('-1 day');

        if ((int)$previousSinceTime->format('U') < (int)$nextSinceTime->format('U')) {

            $response = $this->receiveChangesFromEbay(
                $account,
                array('since_time'=>$nextSinceTime->format('Y-m-d H:i:s'), 'to_time' => $toTime->format('Y-m-d H:i:s'))
            );

            if ($response) {
                return (array)$response;
            }

            $previousSinceTime = $nextSinceTime;
        }

        $nextSinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $nextSinceTime->modify('-2 hours');

        if ((int)$previousSinceTime->format('U') < (int)$nextSinceTime->format('U')) {

            $response = $this->receiveChangesFromEbay(
                $account,
                array('since_time'=>$nextSinceTime->format('Y-m-d H:i:s'), 'to_time' => $toTime->format('Y-m-d H:i:s'))
            );

            if ($response) {
                return (array)$response;
            }
        }

        return array();
    }

    private function receiveChangesFromEbay(\Ess\M2ePro\Model\Account $account, array $paramsConnector = array())
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector('item','get','changes',
                                                            $paramsConnector,NULL,
                                                            NULL,$account->getId());

        $dispatcherObj->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        $this->processResponseMessages($connectorObj->getResponseMessages());

        if (!isset($responseData['items']) || !isset($responseData['to_time'])) {
            return NULL;
        }

        return $responseData;
    }

    private function processResponseMessages(array $messages)
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector\Connection\Response\Message\Set');
        $messagesSet->init($messages);

        foreach ($messagesSet->getEntities() as $message) {

            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    //########################################

    private function prepareSinceTime($sinceTime)
    {
        $minTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $minTime->modify("-1 month");

        if (empty($sinceTime) || strtotime($sinceTime) < (int)$minTime->format('U')) {
            $sinceTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $sinceTime = $sinceTime->format('Y-m-d H:i:s');
        }

        return $sinceTime;
    }

    private function isLockedAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $lockItem \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $lockItem->setNick(self::LOCK_ITEM_PREFIX.'_'.$account->getId());
        $lockItem->setMaxInactiveTime(Runner::MAX_LIFETIME);

        return $lockItem->isExist();
    }

    //########################################
}