<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Update;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    // ########################################

    protected function eventBefore()
    {
        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Amazon\Processing\Action $processingAction */
        $processingAction = $this->activeRecordFactory->getObject('Amazon\Processing\Action');
        $processingAction->setData(array(
            'processing_id' => $this->getProcessingObject()->getId(),
            'account_id'    => $params['account_id'],
            'type'          => \Ess\M2ePro\Model\Amazon\Processing\Action::TYPE_ORDER_UPDATE,
        ));

        $processingAction->save();

        foreach ($params['request_data']['items'] as $changeId => $orderData) {
            /** @var \Ess\M2ePro\Model\Amazon\Processing\Action\Item $processingActionItem */
            $processingActionItem = $this->activeRecordFactory->getObject('Amazon\Processing\Action\Item');
            $processingActionItem->setData(array(
                'action_id'  => $processingAction->getId(),
                'related_id' => $changeId,
                'input_data' => json_encode($orderData),
            ));

            $processingActionItem->save();
        }
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        if (!isset($params['items']) || !is_array($params['items'])) {
            return;
        }

        $ordersIds = array();

        foreach ($params['items'] as $update) {
            if (!isset($update['order_id'])) {
                throw new \Ess\M2ePro\Model\Exception\Logic('Order ID is not defined.');
            }

            $ordersIds[] = (int)$update['order_id'];
        }

        /** @var \Ess\M2ePro\Model\Order[] $orders */
        $orders = $this->activeRecordFactory->getObject('Order')
            ->getCollection()
            ->addFieldToFilter('id', array('in' => $ordersIds))
            ->getItems();

        foreach ($orders as $order) {
            $order->addProcessingLock('update_shipping_status', $this->getProcessingObject()->getId());
        }
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        if (!isset($params['items']) || !is_array($params['items'])) {
            return;
        }

        $ordersIds = array();

        foreach ($params['items'] as $update) {
            if (!isset($update['order_id'])) {
                throw new \Ess\M2ePro\Model\Exception\Logic('Order ID is not defined.');
            }

            $ordersIds[] = (int)$update['order_id'];
        }

        /** @var \Ess\M2ePro\Model\Order $orders */
        $orders = $this->activeRecordFactory->getObject('Order')
            ->getCollection()
            ->addFieldToFilter('id', array('in' => $ordersIds))
            ->getItems();

        foreach ($orders as $order) {
            $order->deleteProcessingLocks('update_shipping_status', $this->getProcessingObject()->getId());
        }
    }

    // ########################################
}