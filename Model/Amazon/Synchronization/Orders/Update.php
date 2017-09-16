<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders;

class Update extends AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/update/';
    }

    protected function getTitle()
    {
        return 'Update';
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
        $this->deleteNotActualChanges();

        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        $iteration = 0;
        $percentsForOneStep = $this->getPercentsInterval() / count($permittedAccounts);

        foreach ($permittedAccounts as $account) {

            /** @var \Ess\M2ePro\Model\Account $account */

            // ---------------------------------------
            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');

            $status = 'The "Update" Action for Amazon Account: "%account_title%" is started. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            // ---------------------------------------

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
                    'The "Update" Action for Amazon Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // ---------------------------------------
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            // ---------------------------------------

            // ---------------------------------------
            $status = 'The "Update" Action for Amazon Account: "%account_title%" is finished. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneStep);
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

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $relatedChanges = $this->getRelatedChanges($account);
        if (empty($relatedChanges)) {
            return;
        }

        $this->activeRecordFactory->getObject('Order\Change')->getResource()
            ->incrementAttemptCount(array_keys($relatedChanges));

        /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');

        foreach ($relatedChanges as $change) {
            $changeParams = $change->getParams();

            $connectorData = array(
                'order_id'         => $change->getOrderId(),
                'change_id'        => $change->getId(),
                'amazon_order_id'  => $changeParams['amazon_order_id'],
                'tracking_number'  => $changeParams['tracking_number'],
                'carrier_name'     => $changeParams['carrier_name'],
                'fulfillment_date' => $changeParams['fulfillment_date'],
                'shipping_method'  => isset($changeParams['shipping_method']) ? $changeParams['shipping_method'] : null,
                'items'            => $changeParams['items']
            );

            $connectorObj = $dispatcherObject->getCustomConnector(
                'Amazon\Synchronization\Orders\Update\Requester',
                array('order' => $connectorData),
                $account
            );
            $dispatcherObject->process($connectorObj);
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return \Ess\M2ePro\Model\Order\Change[]
     */
    private function getRelatedChanges(\Ess\M2ePro\Model\Account $account)
    {
        $changesCollection = $this->activeRecordFactory->getObject('Order\Change')->getCollection();
        $changesCollection->addAccountFilter($account->getId());
        $changesCollection->addProcessingAttemptDateFilter();
        $changesCollection->addFieldToFilter('component', \Ess\M2ePro\Helper\Component\Amazon::NICK);
        $changesCollection->addFieldToFilter('action', \Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_SHIPPING);
        $changesCollection->getSelect()->group(array('order_id'));

        return $changesCollection->getItems();
    }

    // ---------------------------------------

    private function deleteNotActualChanges()
    {
        $this->activeRecordFactory->getObject('Order\Change')->getResource()
            ->deleteByProcessingAttemptCount(
               \Ess\M2ePro\Model\Order\Change::MAX_ALLOWED_PROCESSING_ATTEMPTS,
               \Ess\M2ePro\Helper\Component\Amazon::NICK
            );
    }

    //########################################
}
