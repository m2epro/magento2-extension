<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\OtherListings\Update;

use Ess\M2ePro\Model\Processing\Runner;

class Blocked extends \Ess\M2ePro\Model\Amazon\Synchronization\OtherListings\AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/update/blocked/';
    }

    protected function getTitle()
    {
        return 'Update Blocked Other Listings';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 30;
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

    //########################################

    protected function performActions()
    {
        $accountsCollection = $this->amazonFactory->getObject('Account')->getCollection();
        $accountsCollection->addFieldToFilter('other_listings_synchronization',
           \Ess\M2ePro\Model\Amazon\Account::OTHER_LISTINGS_SYNCHRONIZATION_YES);

        $accounts = $accountsCollection->getItems();

        if (count($accounts) <= 0) {
            return;
        }

        $iteration = 0;
        $percentsForOneStep = $this->getPercentsInterval() / count($accounts);

        foreach ($accounts as $account) {

            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
            // M2ePro\TRANSLATIONS
            // The "3rd Party Listings" Action for Amazon Account: "%account_title%" is started. Please wait...
            $status = 'The "Update Blocked 3rd Party Listings" Action for Amazon Account: ';
            $status .= '"%account_title%" is started. ';
            $status .= 'Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );

            if (!$this->isLockedAccount($account) && !$this->isLockedAccountInterval($account)) {

                $this->getActualOperationHistory()->addTimePoint(
                    __METHOD__.'process'.$account->getId(),
                    'Process Account '.$account->getTitle()
                );

                $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
                $connectorObj = $dispatcherObject->getCustomConnector(
                    'Amazon\Synchronization\OtherListings\Update\Blocked\Requester',
                    array(), $account
                );

                $dispatcherObject->process($connectorObj);

                $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            }

            // M2ePro\TRANSLATIONS
            // The "3rd Party Listings" Action for Amazon Account: "%account_title%" is finished. Please wait...
            $status = 'The "Update Blocked 3rd Party Listings" Action for Amazon Account: ';
            $status .= '"%account_title%" is finished. ';
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

    private function isLockedAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $lockItem \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $lockItem->setNick(Blocked\ProcessingRunner::LOCK_ITEM_PREFIX.'_'.$account->getId());
        $lockItem->setMaxInactiveTime(Runner::MAX_LIFETIME);

        return $lockItem->isExist();
    }

    private function isLockedAccountInterval(\Ess\M2ePro\Model\Account $account)
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