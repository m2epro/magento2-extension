<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Order;

use Ess\M2ePro\Helper\Component\Walmart;
use Ess\M2ePro\Model\Order\Change;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Refund
 */
class Refund extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/order/refund';

    const MAX_ORDERS_CHANGES_COUNT = 50;

    //####################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function performActions()
    {
        $this->deleteNotActualChanges();

        $ordersChangesForProcess = $this->getOrdersChangesForProcess();
        if (empty($ordersChangesForProcess)) {
            return;
        }

        $this->activeRecordFactory->getObject('Order_Change')->getResource()
            ->incrementAttemptCount(array_keys($ordersChangesForProcess));

        foreach ($ordersChangesForProcess as $orderChange) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->parentFactory->getObjectLoaded(Walmart::NICK, 'Order', $orderChange->getOrderId());
            $order->getLog()->setInitiator($orderChange->getCreatorType());

            $actionHandler = $this->modelFactory->getObject('Walmart_Order_Action_Handler_Refund');
            $actionHandler->setOrder($order);
            $actionHandler->setOrderChange($orderChange);

            if ($actionHandler->isNeedProcess()) {
                $actionHandler->process();
            } else {
                $orderChange->delete();
            }
        }
    }

    //####################################

    /**
     * @return Change[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getOrdersChangesForProcess()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Change\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Order_Change')->getCollection();
        $collection->addProcessingAttemptDateFilter();
        $collection->addFieldToFilter('component', Walmart::NICK);
        $collection->addFieldToFilter('action', Change::ACTION_REFUND);
        $collection->getSelect()->limit(self::MAX_ORDERS_CHANGES_COUNT);
        $collection->getSelect()->group('order_id');

        return $collection->getItems();
    }

    protected function deleteNotActualChanges()
    {
        $this->activeRecordFactory->getObject('Order_Change')->getResource()->deleteByProcessingAttemptCount(
            \Ess\M2ePro\Model\Order\Change::MAX_ALLOWED_PROCESSING_ATTEMPTS,
            \Ess\M2ePro\Helper\Component\Walmart::NICK
        );
    }

    //####################################
}
