<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Action\Handler;

use Ess\M2ePro\Model\Walmart\Order\Item as OrderItem;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\Action\Handler\Shipping
 */
class Shipping extends \Ess\M2ePro\Model\Walmart\Order\Action\Handler\AbstractModel
{
    private $params = [];

    //########################################

    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    //########################################

    public function isNeedProcess()
    {
        if (!$this->getWalmartOrder()->isCreated() &&
            !$this->getWalmartOrder()->isUnshipped() &&
            !$this->getWalmartOrder()->isPartiallyShipped()) {
            return false;
        }

        return true;
    }

    //########################################

    protected function getServerCommand()
    {
        return ['orders', 'update', 'shipping'];
    }

    protected function getRequestData()
    {
        $resultItems = [];

        foreach ($this->params['items'] as $itemData) {
            $resultItems[] = [
                'number'           => $itemData['walmart_order_item_id'],
                'qty'              => $itemData['qty'],
                'tracking_details' => $itemData['tracking_details'],
            ];
        }

        return [
            'channel_order_id' => $this->getWalmartOrder()->getWalmartOrderId(),
            'items'            => $resultItems,
        ];
    }

    protected function processResult(array $responseData)
    {
        if (!isset($responseData['result']) || !$responseData['result']) {
            $this->processError();
            return;
        }

        $itemsStatuses = [];

        foreach ($this->params['items'] as $itemData) {
            /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
            $orderItem = $this->walmartFactory->getObject('Order_Item')
                ->getCollection()
                ->addFieldToFilter('order_id', $this->getOrder()->getId())
                ->addFieldToFilter('walmart_order_item_id', $itemData['item_id'])
                ->getFirstItem();

            /**
             * Walmart returns the same Order Item more than one time with single QTY. That data was merged.
             * So walmart_order_item_id of real OrderItem and walmart_order_item_id in request may be different.
             * Real walmart_order_item_id will match with the ID in request when the last item will be shipped.
             */
            if ($orderItem !== null) {
                $orderItem->getChildObject()->setData('status', OrderItem::STATUS_SHIPPED)->save();
                $itemsStatuses[$itemData['walmart_order_item_id']] = OrderItem::STATUS_SHIPPED;
            } else {
                $itemsStatuses[$itemData['walmart_order_item_id']] = OrderItem::STATUS_SHIPPED_PARTIALLY;
            }
        }

        foreach ($this->getOrder()->getItemsCollection() as $item) {
            if (!array_key_exists($item->getChildObject()->getData('walmart_order_item_id'), $itemsStatuses)) {
                $itemsStatuses[$item->getChildObject()->getData('walmart_order_item_id')] =
                    $item->getChildObject()->getData('status');
            }
        }

        $orderStatus = $this->modelFactory->getObject('Walmart_Order_Helper')->getOrderStatus($itemsStatuses);
        $this->getOrder()->getChildObject()->setData('status', $orderStatus);
        $this->getOrder()->getChildObject()->save();
        $this->getOrder()->save();

        $this->getOrder()->getLog()->addMessage(
            $this->getOrder()->getId(),
            $this->helperFactory->getObject('Module\Translation')->__('Order was successfully marked as Shipped.'),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Response\Message[] $messages
     */
    protected function processError(array $messages = [])
    {
        if (empty($messages)) {
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                $this->helperFactory->getObject('Module\Translation')->__(
                    'Order was not shipped due to Walmart error.'
                ),
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $messages = [$message];
        }

        foreach ($messages as $message) {
            $this->getOrder()->getLog()->addMessage(
                $this->getOrder()->getId(),
                $message->getText(),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
            );
        }
    }

    //########################################
}
