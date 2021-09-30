<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Order;

use Ess\M2ePro\Model\Order\Change;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Update
 */
class Update extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/order/update';

    const MAX_UPDATES_PER_TIME = 200;

    //########################################

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

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    //########################################

    protected function performActions()
    {
        $this->deleteNotActualChanges();

        $permittedAccounts = $this->getPermittedAccounts();

        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');

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
        }
    }

    //########################################

    protected function getPermittedAccounts()
    {
        /** @var $accountsCollection \Ess\M2ePro\Model\ResourceModel\Account\Collection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Account'
        )->getCollection();
        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
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

    protected function getRelatedChanges(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Change\Collection $changesCollection */
        $changesCollection = $this->activeRecordFactory->getObject('Order\Change')->getCollection();
        $changesCollection->addAccountFilter($account->getId());
        $changesCollection->addProcessingAttemptDateFilter();
        $changesCollection->addFieldToFilter('action', ['in' => [Change::ACTION_UPDATE_SHIPPING,
                                                                 Change::ACTION_UPDATE_PAYMENT]]);
        $changesCollection->setPageSize(self::MAX_UPDATES_PER_TIME);
        $changesCollection->getSelect()->group(['order_id']);

        return $changesCollection->getItems();
    }

    // ---------------------------------------

    protected function processChange(\Ess\M2ePro\Model\Order\Change $change)
    {
        $this->activeRecordFactory->getObject('Order\Change')->getResource()
            ->incrementAttemptCount([$change->getId()]);
        $connectorData = ['change_id' => $change->getId()];

        if ($change->isPaymentUpdateAction()) {

            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'Order',
                $change->getOrderId()
            );

            if ($order->getId()) {
                $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Order_Dispatcher');
                $dispatcher->process(
                    \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_PAY,
                    [$order],
                    $connectorData
                );
            }

            return;
        }

        if ($change->isShippingUpdateAction()) {
            $changeParams = $change->getParams();

            $action = \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP;
            if (!empty($changeParams['tracking_number']) && !empty($changeParams['carrier_title'])) {
                $action = \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP_TRACK;
                /**
                 * TODO check(rewrite) during orders refactoring.
                 * \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher expects array of order to be proccessed.
                 * But $connectorData has no link to order instance, so appears like discrepancy between these
                 * two parameters.
                 */
                $connectorData['tracking_number'] = $changeParams['tracking_number'];
                $connectorData['carrier_code']    = $changeParams['carrier_title'];
            }

            if (!empty($changeParams['item_id'])) {

                /** @var \Ess\M2ePro\Model\Order\Item $item */
                $item = $this->parentFactory->getObjectLoaded(
                    \Ess\M2ePro\Helper\Component\Ebay::NICK,
                    'Order\Item',
                    $changeParams['item_id']
                );

                if ($item->getId()) {
                    $dispatcher = $this->modelFactory->getObject('Ebay_Connector_OrderItem_Dispatcher');
                    $dispatcher->process($action, [$item], $connectorData);
                }
            } else {

                /** @var \Ess\M2ePro\Model\Order $order */
                $order = $this->parentFactory->getObjectLoaded(
                    \Ess\M2ePro\Helper\Component\Ebay::NICK,
                    'Order',
                    $change->getOrderId()
                );

                if ($order->getId()) {
                    $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Order_Dispatcher');
                    $dispatcher->process($action, [$order], $connectorData);
                }
            }
        }
    }

    //########################################

    protected function deleteNotActualChanges()
    {
        $this->activeRecordFactory->getObject('Order\Change')->getResource()->deleteByProcessingAttemptCount(
            \Ess\M2ePro\Model\Order\Change::MAX_ALLOWED_PROCESSING_ATTEMPTS,
            \Ess\M2ePro\Helper\Component\Ebay::NICK
        );
    }

    //########################################
}
