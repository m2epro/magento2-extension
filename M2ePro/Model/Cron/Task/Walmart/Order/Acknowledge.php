<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Order;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Acknowledge
 */
class Acknowledge extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/order/acknowledge';

    const MAX_ORDERS_COUNT = 50;

    //####################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function performActions()
    {
        $ordersForProcess = $this->getOrdersForProcess();
        if (empty($ordersForProcess)) {
            return;
        }

        foreach ($ordersForProcess as $order) {
            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
            /** @var \Ess\M2ePro\Model\Walmart\Order\Action\Handler\Acknowledge $actionHandler */
            $actionHandler = $this->modelFactory->getObject('Walmart_Order_Action_Handler_Acknowledge');
            $actionHandler->setOrder($order);

            if ($actionHandler->isNeedProcess()) {
                $actionHandler->process();
            }

            $order->setData('is_tried_to_acknowledge', 1);
            $order->save();
        }
    }

    //####################################

    /**
     * @return \Ess\M2ePro\Model\Order[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getOrdersForProcess()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'Order'
        )->getCollection();
        $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED);
        $collection->addFieldToFilter('is_tried_to_acknowledge', 0);
        $collection->getSelect()->order('purchase_create_date ASC');
        $collection->getSelect()->limit(self::MAX_ORDERS_COUNT);

        return $collection->getItems();
    }

    //####################################
}
