<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Orders;

class Update extends AbstractModel
{
    const MAX_UPDATES_PER_TIME = 200;

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
        return 100;
    }

    //########################################

    protected function performActions()
    {
        $this->deleteNotActualChanges();

        $permittedAccounts = $this->getPermittedAccounts();

        if (count($permittedAccounts) <= 0) {
            return;
        }

        $iteration = 1;
        $percentsForOneStep = $this->getPercentsInterval() / count($permittedAccounts);

        foreach ($permittedAccounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
            // M2ePro\TRANSLATIONS
            // The "Update" Action for eBay Account: "%account_title%" is started. Please wait...
            $status = 'The "Update" Action for eBay Account: "%account_title%" is started. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            // ---------------------------------------

            try {

                $this->processAccount($account);

            } catch (\Exception $exception) {

                $message = $this->getHelper('Module\Translation')->__(
                    'The "Update" Action for eBay Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // ---------------------------------------
            // M2ePro\TRANSLATIONS
            // The "Update" Action for eBay Account: "%account_title%" is finished. Please wait...
            $status = 'The "Update" Action for eBay Account: "%account_title%" is finished. Please wait...';
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
        $accountsCollection = $this->ebayFactory->getObject('Account')->getCollection();
        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $changes = $this->getRelatedChanges($account);
        if (empty($changes)) {
            return;
        }

        foreach ($changes as $change) {
            $this->processChange($change);
        }
    }

    //########################################

    private function getRelatedChanges(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Change\Collection $changesCollection */
        $changesCollection = $this->activeRecordFactory->getObject('Order\Change')->getCollection();
        $changesCollection->addAccountFilter($account->getId());
        $changesCollection->addProcessingAttemptDateFilter();
        $changesCollection->setPageSize(self::MAX_UPDATES_PER_TIME);
        $changesCollection->getSelect()->group(array('order_id'));

        return $changesCollection->getItems();
    }

    // ---------------------------------------

    private function processChange(\Ess\M2ePro\Model\Order\Change $change)
    {
        $this->activeRecordFactory->getObject('Order\Change')->getResource()->incrementAttemptCount(
            array($change->getId())
        );

        if ($change->isPaymentUpdateAction()) {

            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->ebayFactory->getObjectLoaded('Order', $change->getOrderId());
            if ($order->getId()) {
                $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Order\Dispatcher');
                $dispatcher->process(\Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_PAY, array($order));
            }

            return;
        }

        if ($change->isShippingUpdateAction()) {
            $changeParams = $change->getParams();
            $params = array();

            $action = \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP;
            if (!empty($changeParams['tracking_details'])) {
                $action = \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP_TRACK;
                $params = $changeParams['tracking_details'];
            }

            if (!empty($changeParams['item_id'])) {

                /** @var \Ess\M2ePro\Model\Order\Item $item */
                $item = $this->ebayFactory->getObjectLoaded('Order\Item', $changeParams['item_id']);

                if ($item->getId()) {
                    $dispatcher = $this->modelFactory->getObject('Ebay\Connector\OrderItem\Dispatcher');
                    $dispatcher->process($action, array($item), $params);
                }
            } else {

                /** @var \Ess\M2ePro\Model\Order $order */
                $order = $this->ebayFactory->getObjectLoaded('Order', $change->getOrderId());

                if ($order->getId()) {
                    $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Order\Dispatcher');
                    $dispatcher->process($action, array($order), $params);
                }
            }
        }
    }

    //########################################

    private function deleteNotActualChanges()
    {
        $this->activeRecordFactory->getObject('Order\Change')->getResource()->deleteByProcessingAttemptCount(
           \Ess\M2ePro\Model\Order\Change::MAX_ALLOWED_PROCESSING_ATTEMPTS,
           \Ess\M2ePro\Helper\Component\Ebay::NICK
        );
    }

    //########################################
}