<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Order;

class Acknowledge extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'walmart/order/acknowledge';

    private const MAX_ORDERS_COUNT = 50;

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function performActions(): void
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

            /** @var \Ess\M2ePro\Model\Walmart\Order $walmartOrder */
            $walmartOrder = $order->getChildObject();
            $walmartOrder->setData('is_tried_to_acknowledge', 1);
            $walmartOrder->save();
        }
    }

    /**
     * @return \Ess\M2ePro\Model\Order[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getOrdersForProcess(): array
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
}
