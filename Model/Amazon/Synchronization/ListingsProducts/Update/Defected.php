<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\ListingsProducts\Update;

use Ess\M2ePro\Model\Processing\Runner;

class Defected extends \Ess\M2ePro\Model\Amazon\Synchronization\ListingsProducts\AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/update/defected/';
    }

    protected function getTitle()
    {
        return 'Update Defected Listings Products';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 25;
    }

    // ---------------------------------------

    protected function intervalIsEnabled()
    {
        return true;
    }

    protected function intervalIsLocked()
    {
        if ($this->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER) {
            return false;
        }

        return parent::intervalIsLocked();
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
            // The "Update Defected Listings Products" Action for Amazon Account: "%account_title%" is started. Please wait...
            $status = 'The "Update Defected Listings Products" Action for Amazon Account: ';
            $status .= '"%account_title%" is started. Please wait...';
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
                    // The "Update Defected Listings Products" Action for Amazon Account: "%account%" was completed with error.
                    $message = 'The "Update Defected Listings Products" Action for Amazon Account "%account%"';
                    $message .= ' was completed with error.';
                    $message = $this->getHelper('Module\Translation')->__($message, $account->getTitle());

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }

                $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            }

            // M2ePro\TRANSLATIONS
            // The "Update Defected Listings Products" Action for Amazon Account: "%account_title%" is finished. Please wait...
            $status = 'The "Update Defected Listings Products" Action for Amazon Account: ';
            $status .= '"%account_title%" is finished. Please wait...';
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
                'Amazon\Synchronization\ListingsProducts\Update\Defected\Requester',
                array(), $account
            );
            $dispatcherObject->process($connectorObj);
        }
    }

    private function isLockedAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $lockItem \Ess\M2ePro\Model\Lock\Item\Manager */
        $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $lockItem->setNick(Defected\ProcessingRunner::LOCK_ITEM_PREFIX.'_'.$account->getId());
        $lockItem->setMaxInactiveTime(Runner::MAX_LIFETIME);

        return $lockItem->isExist();
    }

    //########################################
}