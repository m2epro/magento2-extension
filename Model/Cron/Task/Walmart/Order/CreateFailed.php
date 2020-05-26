<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Order;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Order\CreateFailed
 */
class CreateFailed extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/order/create_failed';

    const MAX_TRIES_TO_CREATE_ORDER = 3;

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            try {
                $this->getOperationHistory()->addText('Starting account "'.$account->getTitle().'"');

                $walmartOrders = $this->getWalmartOrders($account);

                if (!empty($walmartOrders)) {
                    $this->createMagentoOrders($walmartOrders);
                }
            } catch (\Exception $exception) {

                $message = $this->getHelper('Module\Translation')->__(
                    'The "Create Failed Orders" Action for Walmart Account "%account%" was completed with error.',
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
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'Account'
        )->getCollection();
        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    protected function createMagentoOrders($walmartOrders)
    {
        foreach ($walmartOrders as $order) {
            /** @var $order \Ess\M2ePro\Model\Order */

            if ($this->isOrderChangedInParallelProcess($order)) {
                continue;
            }

            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

            if ($order->canCreateMagentoOrder()) {
                try {
                    $message = 'Magento order creation rules are met.';
                    $message .= ' M2E Pro will attempt to create Magento order.';

                    $order->addNoticeLog($message);
                    $order->createMagentoOrder();
                } catch (\Exception $exception) {
                    continue;
                }
            } else {
                $order->addData([
                    'magento_order_creation_failure' => \Ess\M2ePro\Model\Order::MAGENTO_ORDER_CREATION_FAILED_NO,
                    'magento_order_creation_fails_count' => 0,
                    'magento_order_creation_latest_attempt_date' => null
                ]);
                $order->save();

                continue;
            }

            if ($order->getReserve()->isNotProcessed() && $order->isReservable()) {
                $order->getReserve()->place();
            }

            if ($order->getChildObject()->canCreateInvoice()) {
                $order->createInvoice();
            }

            if ($order->getChildObject()->canCreateShipments()) {
                $order->createShipments();
            }

            if ($order->getStatusUpdateRequired()) {
                $order->updateMagentoOrderStatus();
            }
        }
    }

    /**
     * This is going to protect from Magento Orders duplicates.
     * (Is assuming that there may be a parallel process that has already created Magento Order)
     *
     * But this protection is not covering a cases when two parallel cron processes are isolated by mysql transactions
     * @param \Ess\M2ePro\Model\Order $order
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function isOrderChangedInParallelProcess(\Ess\M2ePro\Model\Order $order)
    {
        /** @var \Ess\M2ePro\Model\Order $dbOrder */
        $dbOrder = $this->activeRecordFactory->getObjectLoaded('Order', $order->getId());

        if ($dbOrder->getMagentoOrderId() != $order->getMagentoOrderId()) {
            return true;
        }

        return false;
    }

    // ---------------------------------------

    protected function getWalmartOrders(\Ess\M2ePro\Model\Account $account)
    {
        $backToDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToDate->modify('-15 minutes');

        $collection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'Order'
        )->getCollection();
        $collection->addFieldToFilter('account_id', $account->getId());
        $collection->addFieldToFilter('magento_order_id', ['null' => true]);
        $collection->addFieldToFilter(
            'magento_order_creation_failure',
            \Ess\M2ePro\Model\Order::MAGENTO_ORDER_CREATION_FAILED_YES
        );
        $collection->addFieldToFilter(
            'magento_order_creation_fails_count',
            ['lt' => self::MAX_TRIES_TO_CREATE_ORDER]
        );
        $collection->addFieldToFilter(
            'magento_order_creation_latest_attempt_date',
            ['lt' => $backToDate->format('Y-m-d H:i:s')]
        );
        $collection->getSelect()->order('magento_order_creation_latest_attempt_date ASC');
        $collection->setPageSize(25);

        return $collection->getItems();
    }

    //########################################
}
