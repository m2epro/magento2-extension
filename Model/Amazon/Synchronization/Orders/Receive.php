<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders;

use Ess\M2ePro\Model\Processing\Runner;

final class Receive extends AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/receive/';
    }

    protected function getTitle()
    {
        return 'Receive';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        $iteration = 0;
        $percentsForOneAccount = $this->getPercentsInterval() / count($permittedAccounts);

        foreach ($permittedAccounts as $account) {

            /** @var $account \Ess\M2ePro\Model\Account **/

            // ---------------------------------------
            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
            // M2ePro\TRANSLATIONS
            // The "Receive" Action for Amazon Account: "%account_title%" is started. Please wait...
            $status = 'The "Receive" Action for Amazon Account: "%account_title%" is started. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            // ---------------------------------------

            if (!$this->isLockedAccount($account)) {

                // ---------------------------------------
                $this->getActualOperationHistory()->addTimePoint(
                    __METHOD__.'process'.$account->getId(),
                    'Process Account '.$account->getTitle()
                );
                // ---------------------------------------

                try {

                    $this->processAccount($account);

                } catch (\Exception $exception) {

                    $message = $this->getHelper('Module\Translation')->__(
                        'The "Receive" Action for Amazon Account "%account%" was completed with error.',
                        $account->getTitle()
                    );

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }

                // ---------------------------------------
                $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
                // ---------------------------------------
            }

            // ---------------------------------------
            // M2ePro\TRANSLATIONS
            // The "Receive" Action for Amazon Account: "%account_title%" is finished. Please wait...
            $status = 'The "Receive" Action for Amazon Account: "%account_title%" is finished. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneAccount);
            $this->getActualLockItem()->activate();
            // ---------------------------------------

            $iteration++;
        }
    }

    //########################################

    private function getPermittedAccounts()
    {
        $accountsCollection = $this->amazonFactory->getObject('Account')->getCollection();
        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    private function isLockedAccount(\Ess\M2ePro\Model\Account $account)
    {
        /** @var $lockItem \Ess\M2ePro\Model\LockItem */
        $lockItem = $this->activeRecordFactory->getObject('LockItem');
        $lockItem->setNick(Receive\ProcessingRunner::LOCK_ITEM_PREFIX.'_'.$account->getId());
        $lockItem->setMaxInactiveTime(Runner::MAX_LIFETIME);

        return $lockItem->isExist();
    }

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $fromDate = $this->prepareFromDate($account->getChildObject()->getData('orders_last_synchronization'));
        $toDate   = $this->prepareToDate();

        if (strtotime($fromDate) >= strtotime($toDate)) {
            $fromDate = new \DateTime($toDate, new \DateTimeZone('UTC'));
            $fromDate->modify('- 5 minutes');

            $fromDate = $fromDate->format('Y-m-d H:i:s');
        }

        $params = array(
            'from_date' => $fromDate,
            'to_date'   => $toDate,
        );

        /** @var \Ess\M2ePro\Model\Amazon\Account $amazonAccount */
        $amazonAccount = $account->getChildObject();

        if (is_null($amazonAccount->getData('orders_last_synchronization'))) {
            $amazonAccount->setData('orders_last_synchronization', $fromDate)->save();
        }

        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Amazon\Synchronization\Orders\Receive\Requester', $params, $account
        );
        $dispatcherObject->process($connectorObj);
    }

    //########################################

    private function prepareFromDate($lastFromDate)
    {
        // Get last from date
        // ---------------------------------------
        if (is_null($lastFromDate)) {
            $lastFromDate = new \DateTime('now', new \DateTimeZone('UTC'));
        } else {
            $lastFromDate = new \DateTime($lastFromDate, new \DateTimeZone('UTC'));
        }
        // ---------------------------------------

        // Get min date for synch
        // ---------------------------------------
        $minDate = new \DateTime('now',new \DateTimeZone('UTC'));
        $minDate->modify('-7 days');
        // ---------------------------------------

        // Prepare last date
        // ---------------------------------------
        if ((int)$lastFromDate->format('U') < (int)$minDate->format('U')) {
            $lastFromDate = $minDate;
        }
        // ---------------------------------------

        return $lastFromDate->format('Y-m-d H:i:s');
    }

    private function prepareToDate()
    {
        $operationHistory = $this->getActualOperationHistory()->getParentObject('synchronization');
        if (!is_null($operationHistory)) {
            $toDate = $operationHistory->getData('start_date');
        } else {
            $toDate = new \DateTime('now', new \DateTimeZone('UTC'));
            $toDate = $toDate->format('Y-m-d H:i:s');
        }

        return $toDate;
    }

    //########################################
}