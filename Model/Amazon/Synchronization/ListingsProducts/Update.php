<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\ListingsProducts;

use Ess\M2ePro\Model\Processing\Runner;

class Update extends AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/update/';
    }

    protected function getTitle()
    {
        return 'Update Listings Products';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 50;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    // ---------------------------------------

    protected function intervalIsEnabled()
    {
        return true;
    }

    protected function intervalIsLocked()
    {
        if ($this->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_USER ||
            $this->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER) {
            return false;
        }

        return parent::intervalIsLocked();
    }

    protected function intervalGetLastTime()
    {
        $currentLastTime = parent::intervalGetLastTime();

        if (empty($currentLastTime)) {
            return null;
        }

        if (!in_array(\Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS,
            $this->getAllowedTasksTypes())) {
            return $currentLastTime;
        }

        $otherListingsLastTime = $this->getConfigValue('/amazon/other_listings/update/', 'last_time');

        if (empty($otherListingsLastTime)) {
            return null;
        }

        if (strtotime($otherListingsLastTime) < strtotime($currentLastTime)) {
            return $otherListingsLastTime;
        }

        return $currentLastTime;
    }

    //########################################

    protected function performActions()
    {
        $accounts = $this->amazonFactory->getObject('Account')->getCollection()->getItems();

        if (count($accounts) <= 0) {
            return;
        }

        $iteration = 0;
        $percentsForOneStep = $this->getPercentsInterval() / count($accounts);

        foreach ($accounts as $account) {

            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
            // M2ePro\TRANSLATIONS
            // The "Update Listings Products" Action for Amazon Account: "%account_title%" is started. Please wait...
            $status = 'The "Update Listings Products" Action for Amazon Account: "%account_title%" is started. ';
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

                    $this->processAccount($account);

                } catch (\Exception $exception) {

                    // M2ePro_TRANSLATIONS
                    // The "Update Listings Products" Action for Amazon Account: "%account%" was completed with error.
                    $message = 'The "Update Listings Products" Action for Amazon Account "%account%"';
                    $message .= ' was completed with error.';
                    $message = $this->getHelper('Module\Translation')->__($message, $account->getTitle());

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }

                $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            }

            // M2ePro\TRANSLATIONS
            // The "Update Listings Products" Action for Amazon Account: "%account_title%" is finished. Please wait...
            $status = 'The "Update Listings Products" Action for Amazon Account: "%account_title%" is finished. ';
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

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $collection->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Amazon::NICK);
        $collection->addFieldToFilter('account_id',(int)$account->getId());

        if ($collection->getSize()) {

            $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getCustomConnector(
                'Amazon\Synchronization\ListingsProducts\Update\Requester', array(), $account
            );
            $dispatcherObject->process($connectorObj);
        }
    }

    private function isLockedAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $lockItem \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $lockItem->setNick(Update\ProcessingRunner::LOCK_ITEM_PREFIX.'_'.$account->getId());
        $lockItem->setMaxInactiveTime(Runner::MAX_LIFETIME);

        return $lockItem->isExist();
    }

    //########################################
}