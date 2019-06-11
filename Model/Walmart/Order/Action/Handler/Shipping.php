<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Action\Handler;

use Ess\M2ePro\Model\Walmart\Order\Item as OrderItem;

class Shipping extends \Ess\M2ePro\Model\Walmart\Order\Action\Handler\AbstractModel
{
    private $params = array();

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
        return array('orders', 'update', 'shipping');
    }

    protected function getRequestData()
    {
        $resultItems = array();

        foreach ($this->params['items'] as $itemData) {
            $resultItems[] = array(
                'number'           => $itemData['walmart_order_item_id'],
                'qty'              => $itemData['qty'],
                'tracking_details' => $itemData['tracking_details'],
            );
        }

        return array(
            'channel_order_id' => $this->getWalmartOrder()->getWalmartOrderId(),
            'items'            => $resultItems,
        );
    }

    protected function processResult(array $responseData)
    {
        if (!isset($responseData['result']) || !$responseData['result']) {
            $this->processError();
            return;
        }

        $itemsStatuses = array();

        foreach ($this->params['items'] as $itemData) {

            /** @var \Ess\M2ePro\Model\Walmart\Order\Item $orderItem */
            $orderItem = $this->activeRecordFactory->getObjectLoaded(
                'Walmart\Order\Item', $itemData['walmart_order_item_id'], 'walmart_order_item_id'
            );

            /**
             * Walmart returns the same Order Item more than one time with single QTY. That data was merged.
             * So walmart_order_item_id of real OrderItem and walmart_order_item_id in request may be different.
             * Real walmart_order_item_id will match with the ID in request when the last item will be shipped.
             */
            if (!is_null($orderItem)) {
                $orderItem->setData('status', OrderItem::STATUS_SHIPPED)->save();
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

        $orderStatus = $this->modelFactory->getObject('Walmart\Order\Helper')->getOrderStatus($itemsStatuses);
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
    protected function processError(array $messages = array())
    {
        if (empty($messages)) {
            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                $this->helperFactory->getObject('Module\Translation')
                                    ->__('Order was not shipped due to Walmart error.'),
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $messages = array($message);
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